<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Cycle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cycle>
 */
class CycleRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Cycle::class);
    }
}
