<?php

namespace App\Repository;

use App\Entity\Music;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for {@see Music} entities.
 *
 * This repository provides:
 * - Lookup helpers for Spotify-linked tracks (spotifyId or public Spotify URL).
 * - Lightweight search used by merged local+Spotify search endpoints.
 * - Helpers used by artist-related endpoints (count/list musics per artist).
 *
 * @extends ServiceEntityRepository<Music>
 */
class MusicRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry Doctrine manager registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Music::class);
    }

    /**
     * Finds a music by Spotify identifier.
     *
     * @param string $spotifyId Spotify track id.
     *
     * @return Music|null The music if found, otherwise null.
     */
    public function findOneBySpotifyId(string $spotifyId): ?Music
    {
        $spotifyId = trim($spotifyId);
        if ($spotifyId === '') {
            return null;
        }

        return $this->createQueryBuilder('m')
            ->andWhere('m.spotifyId = :sid')
            ->setParameter('sid', $spotifyId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Finds musics by a list of Spotify identifiers.
     *
     * @param string[] $spotifyIds Spotify track ids.
     *
     * @return Music[] Matching musics.
     */
    public function findBySpotifyIds(array $spotifyIds): array
    {
        $spotifyIds = array_values(array_unique(array_filter(array_map('trim', $spotifyIds))));
        if (count($spotifyIds) === 0) {
            return [];
        }

        return $this->createQueryBuilder('m')
            ->andWhere('m.spotifyId IN (:sids)')
            ->setParameter('sids', $spotifyIds)
            ->getQuery()
            ->getResult();
    }

    /**
     * Finds a music by its public Spotify URL.
     *
     * This is used as a fallback match strategy when spotifyId is not present.
     *
     * @param string $link Public Spotify URL.
     *
     * @return Music|null The music if found, otherwise null.
     */
    public function findOneByLink(string $link): ?Music
    {
        $link = trim($link);
        if ($link === '') {
            return null;
        }

        return $this->createQueryBuilder('m')
            ->andWhere('m.link = :link')
            ->setParameter('link', $link)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Counts local musics matching a case-insensitive LIKE search on title or artist name.
     *
     * @param string $query User query.
     *
     * @return int Number of matching musics.
     */
    public function countLocalSearch(string $query): int
    {
        $q = trim($query);
        if ($q === '') {
            return 0;
        }

        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.id)')
            ->leftJoin('m.artists', 'a')
            ->andWhere('LOWER(m.title) LIKE LOWER(:q) OR LOWER(a.name) LIKE LOWER(:q)')
            ->setParameter('q', '%' . $q . '%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Searches local musics using a case-insensitive LIKE filter on title or artist name.
     *
     * @param string $query User query.
     * @param int $limit Max results (clamped to 1..50).
     * @param int $offset Offset.
     *
     * @return Music[] Matching musics ordered by popularity and id.
     */
    public function searchLocal(string $query, int $limit, int $offset): array
    {
        $q = trim($query);
        if ($q === '' || $limit <= 0) {
            return [];
        }

        return $this->createQueryBuilder('m')
            ->select('DISTINCT m, a')
            ->leftJoin('m.artists', 'a')
            ->andWhere('LOWER(m.title) LIKE LOWER(:q) OR LOWER(a.name) LIKE LOWER(:q)')
            ->setParameter('q', '%' . $q . '%')
            ->orderBy('m.popularity', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(min(50, max(1, $limit)))
            ->getQuery()
            ->getResult();
    }

    /**
     * Counts musics imported under a specific import source.
     *
     * @param string $source Import source tag stored in the entity.
     *
     * @return int Number of matching musics.
     */
    public function countNewReleases(string $source = 'spotify:new_releases'): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(m.id)')
            ->andWhere('m.importSource = :src')
            ->setParameter('src', $source)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns musics imported under a specific import source.
     *
     * @param string $source Import source tag stored in the entity.
     * @param int $limit Max results (clamped to 1..50).
     * @param int $offset Offset.
     *
     * @return Music[] Matching musics ordered by importedAt, popularity and id.
     */
    public function findNewReleases(string $source, int $limit, int $offset): array
    {
        return $this->createQueryBuilder('m')
            ->select('m, a')
            ->leftJoin('m.artists', 'a')
            ->andWhere('m.importSource = :src')
            ->setParameter('src', $source)
            ->orderBy('m.importedAt', 'DESC')
            ->addOrderBy('m.popularity', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(min(50, max(1, $limit)))
            ->getQuery()
            ->getResult();
    }

    /**
     * Counts musics linked to a given artist (local database).
     *
     * @param int $artistId Local artist id.
     *
     * @return int Number of linked musics.
     */
    public function countByArtistId(int $artistId): int
    {
        return (int) $this->createQueryBuilder('m')
            ->select('COUNT(DISTINCT m.id)')
            ->innerJoin('m.artists', 'a')
            ->andWhere('a.id = :aid')
            ->setParameter('aid', $artistId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Returns musics linked to a given artist (local database).
     *
     * @param int $artistId Local artist id.
     * @param int $limit Max results (clamped to 1..50).
     * @param int $offset Offset.
     *
     * @return Music[] Linked musics ordered by popularity and id.
     */
    public function findByArtistId(int $artistId, int $limit, int $offset): array
    {
        if ($limit <= 0) {
            return [];
        }

        return $this->createQueryBuilder('m')
            ->select('DISTINCT m, a')
            ->innerJoin('m.artists', 'a')
            ->andWhere('a.id = :aid')
            ->setParameter('aid', $artistId)
            ->orderBy('m.popularity', 'DESC')
            ->addOrderBy('m.id', 'DESC')
            ->setFirstResult(max(0, $offset))
            ->setMaxResults(min(50, max(1, $limit)))
            ->getQuery()
            ->getResult();
    }
}
