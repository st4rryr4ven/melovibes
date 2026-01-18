<?php

namespace App\Security\Voter;

use App\Entity\Review;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Authorization voter for {@see Review} ownership.
 *
 * Current policy:
 * - A review author can edit their own review.
 * - A review author can delete their own review.
 * - Administrators can delete any review.
 */
class ReviewVoter extends Voter
{
    /**
     * Attribute used to edit a review.
     */
    public const EDIT = 'REVIEW_EDIT';

    /**
     * Attribute used to delete a review.
     */
    public const DELETE = 'REVIEW_DELETE';

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
        return in_array($attribute, [self::EDIT, self::DELETE], true)
            && $subject instanceof Review;
    }

    /**
     * Performs the authorization decision.
     *
     * @param string $attribute The requested permission.
     * @param mixed $subject The secured subject (a {@see Review}).
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

        $review = $subject;

        return match ($attribute) {
            self::EDIT => $review->getAuthor() === $user,
            self::DELETE => $review->getAuthor() === $user || in_array('ROLE_ADMIN', $user->getRoles(), true),
            default => false,
        };
    }
}
