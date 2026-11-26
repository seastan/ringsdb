<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Cycle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Cycle>
 */
class CycleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cycle::class);
    }

    /**
     * Every cycle with its packs, in one query.
     *
     * @return list<Cycle>
     */
    public function findAllWithPacks(): array
    {
        return $this->createQueryBuilder('c')
            ->addSelect('p')
            ->leftJoin('c.packs', 'p')
            ->orderBy('c.position', 'ASC')
            ->addOrderBy('p.position', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
