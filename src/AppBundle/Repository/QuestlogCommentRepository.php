<?php

namespace AppBundle\Repository;

use AppBundle\Entity\QuestlogComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QuestlogComment>
 */
class QuestlogCommentRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, QuestlogComment::class);
    }
}
