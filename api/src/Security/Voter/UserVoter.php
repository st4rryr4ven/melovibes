<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class UserVoter extends Voter
{
    public const VIEW = 'USER_VIEW';
    public const EDIT = 'USER_EDIT';
    public const DELETE = 'USER_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $me = $token->getUser();
        if (!$me instanceof UserInterface) {
            return false;
        }

        /** @var User $targetUser */
        $targetUser = $subject;
        $meIsAdmin = in_array('ROLE_ADMIN', $me->getRoles());
        $targetIsAdmin = in_array('ROLE_ADMIN', $targetUser->getRoles());

        return match ($attribute) {
            self::VIEW => $meIsAdmin || ($me === $targetUser),

            self::EDIT => ($me === $targetUser),

            self::DELETE => ($me === $targetUser) || ($meIsAdmin && !$targetIsAdmin),

            default => false,
        };
    }
}
