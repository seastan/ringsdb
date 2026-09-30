<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Questlog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Questlog>
 */
class QuestlogRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Questlog::class);
    }
}
