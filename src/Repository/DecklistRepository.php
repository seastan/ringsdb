<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Decklist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Decklist>
 */
class DecklistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Decklist::class);
    }

    /**
     * Loads the slots, cards, types and spheres of the decklists in one query, so that
     * getHeroDeck() runs without a query.
     *
     * The slots are not filtered on the hero type: Doctrine would mark the partial collections as
     * initialized.
     *
     * @param iterable<Decklist> $decklists
     */
    public function loadSlots(iterable $decklists): void
    {
        $ids = [];
        foreach ($decklists as $decklist) {
            $ids[$decklist->getId()] = $decklist->getId();
        }

        if ([] === $ids) {
            return;
        }

        $this->getEntityManager()
            ->createQuery('SELECT d, s, c, t, sp FROM '.Decklist::class.' d LEFT JOIN d.slots s LEFT JOIN s.card c LEFT JOIN c.type t LEFT JOIN c.sphere sp WHERE d.id IN (:ids)')
            ->setParameter('ids', array_values($ids))
            ->getResult();
    }
}
