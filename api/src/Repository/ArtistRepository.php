<?php

namespace App\Repository;

use App\Entity\Artist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artist>
 */
class ArtistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artist::class);
    }

    public function findOneBySpotifyId(string $spotifyId): ?Artist
    {
        $spotifyId = trim($spotifyId);
        if ($spotifyId === '') {
            return null;
        }

        return $this->createQueryBuilder('a')
            ->andWhere('a.spotifyId = :sid')
            ->setParameter('sid', $spotifyId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @param string[] $spotifyIds
     * @return Artist[]
     */
    public function findBySpotifyIds(array $spotifyIds): array
    {
        $spotifyIds = array_values(array_unique(array_filter(array_map('trim', $spotifyIds))));
        if (count($spotifyIds) === 0) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->andWhere('a.spotifyId IN (:sids)')
            ->setParameter('sids', $spotifyIds)
            ->getQuery()
            ->getResult();
    }

    public function findOneByName(string $name): ?Artist
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }

        return $this->createQueryBuilder('a')
            ->andWhere('LOWER(a.name) = LOWER(:name)')
            ->setParameter('name', $name)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countLocalSearch(string $query): int
    {
        $q = trim($query);
        if ($q === '') {
            return 0;
        }

        return (int) $this->createQueryBuilder('a')
            ->select('COUNT(DISTINCT a.id)')
            ->andWhere('LOWER(a.name) LIKE LOWER(:q)')
            ->setParameter('q', '%' . $q . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Artist[]
     */
    public function searchLocal(string $query, int $limit, int $offset): array
    {
        $q = trim($query);
        if ($q === '' || $limit <= 0) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->andWhere('LOWER(a.name) LIKE LOWER(:q)')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('a.name', 'ASC')
            ->addOrderBy('a.id', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(min(50, max(1, $limit)))
            ->getQuery()
            ->getResult();
    }
}
