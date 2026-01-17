<?php

namespace App\Security\Voter;

use App\Entity\Music;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class MusicVoter extends Voter
{
    public const VALIDATE = 'MUSIC_VALIDATE';
    public const DELETE = 'MUSIC_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VALIDATE, self::DELETE])
            && $subject instanceof Music;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}
