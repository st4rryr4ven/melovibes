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
 * This prevents the browser from continuing to send a JWT/refresh cookie that references a deleted user,
 * which would otherwise cause repeated "Invalid credentials" responses on subsequent requests.
 */
final readonly class ClearAuthCookiesOnUserDeleteSubscriber implements EventSubscriberInterface
{
    /**
     * @param Security $security Symfony security helper.
     */
    public function __construct(
        private Security $security
    ) {
    }

    /**
     * {@inheritDoc}
     *
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    /**
     * Adds Set-Cookie headers to remove auth cookies after a successful self user deletion.
     *
     * @param ResponseEvent $event Symfony response event.
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
