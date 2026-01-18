<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Authorization voter for {@see User} resource access.
 *
 * Current policy:
 * - VIEW: admins can view any user; users can view themselves.
 * - EDIT: users can edit themselves.
 * - DELETE: users can delete themselves; admins can delete non-admin users.
 */
class UserVoter extends Voter
{
    /**
     * Attribute used to view a user.
     */
    public const VIEW = 'USER_VIEW';

    /**
     * Attribute used to edit a user.
     */
    public const EDIT = 'USER_EDIT';

    /**
     * Attribute used to delete a user.
     */
    public const DELETE = 'USER_DELETE';

    /**
     * Determines whether this voter supports the given attribute and subject.
     *
     * @param string $attribute The requested permission.
     * @param mixed $subject The subject being secured.
     *
     * @return bool True when this voter can decide, otherwise false.
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE], true)
            && $subject instanceof User;
    }

    /**
     * Performs the authorization decision.
     *
     * @param string $attribute The requested permission.
     * @param mixed $subject The secured subject (a {@see User}).
     * @param TokenInterface $token Security token.
     *
     * @return bool True when access is granted, otherwise false.
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $me = $token->getUser();
        if (!$me instanceof UserInterface) {
            return false;
        }

        $targetUser = $subject;
        $meIsAdmin = in_array('ROLE_ADMIN', $me->getRoles(), true);
        $targetIsAdmin = in_array('ROLE_ADMIN', $targetUser->getRoles(), true);

        return match ($attribute) {
            self::VIEW => $meIsAdmin || ($me === $targetUser),
            self::EDIT => ($me === $targetUser),
            self::DELETE => ($me === $targetUser) || ($meIsAdmin && !$targetIsAdmin),
            default => false,
        };
    }
}
