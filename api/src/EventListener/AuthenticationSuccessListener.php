<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

/**
 * Enriches the LexikJWT "authentication success" response payload.
 *
 * This listener ensures a token is present in the response and adds basic user information:
 * - id, login, email
 * - token_exp (when the token can be parsed)
 */
readonly class AuthenticationSuccessListener
{
    /**
     * @param JWTTokenManagerInterface $jwtManager JWT manager used to create and parse tokens.
     */
    public function __construct(
        private JWTTokenManagerInterface $jwtManager
    ) {
    }

    /**
     * Adds token and user details to the authentication success JSON response.
     *
     * @param AuthenticationSuccessEvent $event The Lexik authentication success event.
     *
     * @return void
     */
    #[AsEventListener('lexik_jwt_authentication.on_authentication_success')]
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event): void
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (empty($data['token'])) {
            $data['token'] = $this->jwtManager->create($user);
        }

        $data['id'] = $user->getId();
        $data['login'] = $user->getLogin();
        $data['email'] = $user->getEmail();

        if (!empty($data['token'])) {
            try {
                $jwt = $this->jwtManager->parse($data['token']);
                $data['token_exp'] = $jwt['exp'] ?? null;
            } catch (\Exception) {
                $data['token_exp'] = null;
            }
        }

        $event->setData($data);
    }
}
