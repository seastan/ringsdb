<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Encounter;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Encounter>
 */
class EncounterRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Encounter::class);
    }
}
