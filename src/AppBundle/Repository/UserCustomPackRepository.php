<?php

namespace AppBundle\Repository;

use AppBundle\Entity\UserCustomPack;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserCustomPack>
 */
class UserCustomPackRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, UserCustomPack::class);
    }
}
