<?php

namespace App\Repository;

use App\Entity\Music;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Music>
 */
class MusicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Music::class);
    }

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
     * @param string[] $spotifyIds
     * @return Music[]
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
     * @return Music[]
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
     * @return Music[]
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
     * @return Music[]
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
