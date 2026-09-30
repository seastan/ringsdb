<?php

namespace AppBundle\Repository;

use AppBundle\Entity\FellowshipComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<FellowshipComment>
 */
class FellowshipCommentRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, FellowshipComment::class);
    }
}
