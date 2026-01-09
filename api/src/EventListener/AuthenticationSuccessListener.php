<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

class AuthenticationSuccessListener
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager
    )
    {
    }

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

        if (isset($data['token']) && !empty($data['token'])) {
            try {
                $jwt = $this->jwtManager->parse($data['token']);
                $data['token_exp'] = $jwt['exp'] ?? null;
            } catch (\Exception $e) {
                $data['token_exp'] = null;
            }
        }

        $event->setData($data);
    }
}
