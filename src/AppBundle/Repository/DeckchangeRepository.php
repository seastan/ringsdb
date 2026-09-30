<?php

namespace AppBundle\Repository;

use AppBundle\Entity\Deckchange;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deckchange>
 */
class DeckchangeRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, Deckchange::class);
    }
}
