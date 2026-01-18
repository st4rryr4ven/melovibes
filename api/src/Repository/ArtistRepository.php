<?php

namespace App\Repository;

use App\Entity\Artist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for {@see Artist} entities.
 *
 * This repository provides:
 * - Lookup helpers for Spotify-linked artists.
 * - Lightweight, case-insensitive local search used by merged local+Spotify search endpoints.
 *
 * @extends ServiceEntityRepository<Artist>
 */
class ArtistRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry Doctrine manager registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artist::class);
    }

    /**
     * Finds an artist by Spotify identifier.
     *
     * @param string $spotifyId Spotify artist id.
     *
     * @return Artist|null The artist if found, otherwise null.
     */
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
     * Finds artists by a list of Spotify identifiers.
     *
     * @param string[] $spotifyIds Spotify artist ids.
     *
     * @return Artist[] Matching artists.
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

    /**
     * Finds an artist by name (case-insensitive exact match).
     *
     * @param string $name Artist name.
     *
     * @return Artist|null The artist if found, otherwise null.
     */
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

    /**
     * Counts local artists matching a case-insensitive LIKE search.
     *
     * @param string $query User query.
     *
     * @return int Number of matching artists.
     */
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
     * Searches local artists by name using a case-insensitive LIKE filter.
     *
     * @param string $query User query.
     * @param int $limit Max results (clamped to 1..50).
     * @param int $offset Offset.
     *
     * @return Artist[] Matching artists ordered by name and id.
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
