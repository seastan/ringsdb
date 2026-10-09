<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Deck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Deck>
 */
class DeckRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Deck::class);
    }

    /**
     * Loads the slots and side slots of the decks, in one query each (joining both would return
     * their product).
     *
     * @param iterable<Deck> $decks
     */
    public function loadSlots(iterable $decks): void
    {
        $ids = [];
        foreach ($decks as $deck) {
            $ids[$deck->getId()] = $deck->getId();
        }

        if ([] === $ids) {
            return;
        }

        foreach (['slots', 'sideslots'] as $association) {
            $this->getEntityManager()
                ->createQuery('SELECT d, s FROM '.Deck::class." d LEFT JOIN d.$association s WHERE d.id IN (:ids)")
                ->setParameter('ids', array_values($ids))
                ->getResult();
        }
    }
}
