<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for {@see User} entities.
 *
 * This repository provides a helper lookup for Spotify-linked users.
 *
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry Doctrine manager registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Finds a user by Spotify identifier.
     *
     * @param string $spotifyId Spotify user id.
     *
     * @return User|null The user if found, otherwise null.
     */
    public function findOneBySpotifyId(string $spotifyId): ?User
    {
        $spotifyId = trim($spotifyId);
        if ($spotifyId === '') {
            return null;
        }

        return $this->createQueryBuilder('u')
            ->andWhere('u.spotifyId = :sid')
            ->setParameter('sid', $spotifyId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
