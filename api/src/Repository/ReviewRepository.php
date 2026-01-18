<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository for {@see Review} entities.
 *
 * No custom query methods are currently required for this project.
 *
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    /**
     * @param ManagerRegistry $registry Doctrine manager registry.
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }
}
