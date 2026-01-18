<?php

namespace App\Security\Voter;

use App\Entity\Music;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Authorization voter for {@see Music} moderation actions.
 *
 * Current policy:
 * - Only administrators can validate or delete musics through protected operations.
 */
class MusicVoter extends Voter
{
    /**
     * Attribute used to validate a music.
     */
    public const VALIDATE = 'MUSIC_VALIDATE';

    /**
     * Attribute used to delete a music.
     */
    public const DELETE = 'MUSIC_DELETE';

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
        return in_array($attribute, [self::VALIDATE, self::DELETE], true)
            && $subject instanceof Music;
    }

    /**
     * Performs the authorization decision.
     *
     * @param string $attribute The requested permission.
     * @param mixed $subject The secured subject (a {@see Music}).
     * @param TokenInterface $token Security token.
     *
     * @return bool True when access is granted, otherwise false.
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) {
            return false;
        }

        return in_array('ROLE_ADMIN', $user->getRoles(), true);
    }
}
