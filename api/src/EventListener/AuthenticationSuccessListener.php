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
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        $data['id'] = $user->getId();
        $data['login'] = $user->getLogin();
        $data['adresseEmail'] = $user->getEmail();

        $jwt = $this->jwtManager->parse($data['token']);
        $data['token_exp'] = $jwtPayload['exp'] ?? null;

        $event->setData($data);
    }
}
