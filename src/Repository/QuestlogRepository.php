<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Questlog;
use App\Entity\QuestlogDeck;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Questlog>
 */
class QuestlogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Questlog::class);
    }

    /**
     * Loads the decks of the quest logs, with their decklist and private deck, in one query.
     *
     * The quest log decks are returned, so that their slots can be loaded next
     * (DecklistRepository::loadSlots(), SnapshotManager::setSnapshots()).
     *
     * @param iterable<Questlog> $questlogs
     *
     * @return list<QuestlogDeck>
     */
    public function loadDecks(iterable $questlogs): array
    {
        $ids = [];
        foreach ($questlogs as $questlog) {
            $ids[$questlog->getId()] = $questlog->getId();
        }

        if ([] === $ids) {
            return [];
        }

        $this->getEntityManager()
            ->createQuery('SELECT q, qd, dl, d FROM '.Questlog::class.' q LEFT JOIN q.decks qd LEFT JOIN qd.decklist dl LEFT JOIN qd.deck d WHERE q.id IN (:ids) ORDER BY qd.id')
            ->setParameter('ids', array_values($ids))
            ->getResult();

        $questlogDecks = [];
        foreach ($questlogs as $questlog) {
            foreach ($questlog->getDecks() as $questlogDeck) {
                $questlogDecks[] = $questlogDeck;
            }
        }

        return $questlogDecks;
    }
}
