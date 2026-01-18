<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Event\LogoutEvent;

/**
 * Normalizes the API logout response.
 *
 * For API clients, a 204 No Content response is often preferable to an HTML redirect.
 * This subscriber replaces the default logout response with an empty 204 response.
 */
final class ApiLogoutSubscriber implements EventSubscriberInterface
{
    /**
     * Declares subscribed events.
     *
     * @return array<class-string, string> Event map.
     */
    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    /**
     * Overrides the logout response.
     *
     * @param LogoutEvent $event Logout event.
     *
     * @return void
     */
    public function onLogout(LogoutEvent $event): void
    {
        $event->setResponse(new Response('', Response::HTTP_NO_CONTENT));
    }
}
