<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Fellowship;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Fellowship>
 */
class FellowshipRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Fellowship::class);
    }
}
