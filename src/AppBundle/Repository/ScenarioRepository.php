<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Scenario;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Scenario>
 */
class ScenarioRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Scenario::class);
    }
}
