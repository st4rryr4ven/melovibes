<?php

namespace App\EventSubscriber;

use App\Entity\User;
use DateTimeImmutable;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Clears authentication cookies when the currently authenticated user deletes their own account.
 *
 * Context:
 * - The frontend relies on HTTP-only cookies (e.g. "BEARER" JWT and "refresh_token") for authentication.
 * - When a user deletes their account, those cookies may remain in the browser.
 * - Subsequent requests would keep sending cookies referencing a deleted user, which typically produces repeated
 *   "Invalid credentials" responses and a confusing UX (appears "still logged in" but unauthorized).
 *
 * Behavior:
 * - Listens on the kernel.response event and checks for a successful DELETE /api/users/{id} response.
 * - If the deleted {id} matches the currently authenticated user, it issues Set-Cookie headers to expire
 *   the authentication cookies immediately.
 *
 * Notes:
 * - This subscriber is intentionally conservative and will do nothing for non-main requests, non-DELETE methods,
 *   non-user routes, error responses (>= 400), unauthenticated requests, or when the deleted id does not match
 *   the current user.
 */
final readonly class ClearAuthCookiesOnUserDeleteSubscriber implements EventSubscriberInterface
{
    /**
     * @param Security $security Security helper used to resolve the currently authenticated user.
     */
    public function __construct(
        private Security $security
    ) {
    }

    /**
     * Declares the events this subscriber listens to.
     *
     * The subscriber runs on {@see KernelEvents::RESPONSE} so it can safely modify the outgoing HTTP response
     * after the delete operation is processed by API Platform / Symfony.
     *
     * @return array<string, string> Event map in the form [eventName => methodName].
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * Adds Set-Cookie headers that expire authentication cookies after a successful self-account deletion.
     *
     * Matching criteria:
     * - Main request only.
     * - HTTP method is DELETE.
     * - Path starts with "/api/users/".
     * - Response status code is < 400.
     * - Current authenticated user is a {@see User}.
     * - Deleted user id equals authenticated user id.
     *
     * Cookies expired:
     * - "BEARER"
     * - "refresh_token"
     *
     * @param ResponseEvent $event Kernel response event.
     *
     * @return void
     */
    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->getMethod() !== 'DELETE') {
            return;
        }

        if (!str_starts_with($request->getPathInfo(), '/api/users/')) {
            return;
        }

        $response = $event->getResponse();
        if ($response->getStatusCode() >= 400) {
            return;
        }

        $authenticated = $this->security->getUser();
        if (!$authenticated instanceof User) {
            return;
        }

        $deletedId = $request->attributes->get('id');

        if ($deletedId === null) {
            if (preg_match('#^/api/users/(\d+)$#', $request->getPathInfo(), $m) === 1) {
                $deletedId = (int) $m[1];
            }
        }

        if ($deletedId === null || (int) $deletedId !== (int) ($authenticated->getId() ?? 0)) {
            return;
        }

        $expiredAt = new DateTimeImmutable('@0');

        $response->headers->setCookie(
            Cookie::create('BEARER')
                ->withValue('')
                ->withExpires($expiredAt)
                ->withPath('/')
                ->withHttpOnly(true)
                ->withSecure(false)
                ->withSameSite('lax')
        );

        $response->headers->setCookie(
            Cookie::create('refresh_token')
                ->withValue('')
                ->withExpires($expiredAt)
                ->withPath('/')
                ->withHttpOnly(true)
                ->withSecure(false)
                ->withSameSite('lax')
        );
    }
}
