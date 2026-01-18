<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

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
