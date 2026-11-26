<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Decklist;
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

    /**
     * Loads the decklists of the fellowships, with their last pack, in one query.
     *
     * The decklists are returned, so that their slots can be loaded next
     * (DecklistRepository::loadSlots()).
     *
     * @param iterable<Fellowship> $fellowships
     *
     * @return list<Decklist>
     */
    public function loadDecklists(iterable $fellowships): array
    {
        $ids = [];
        foreach ($fellowships as $fellowship) {
            $ids[$fellowship->getId()] = $fellowship->getId();
        }

        if ([] === $ids) {
            return [];
        }

        $this->getEntityManager()
            ->createQuery('SELECT f, fd, d, p FROM '.Fellowship::class.' f LEFT JOIN f.decklists fd LEFT JOIN fd.decklist d LEFT JOIN d.lastPack p WHERE f.id IN (:ids) ORDER BY fd.id')
            ->setParameter('ids', array_values($ids))
            ->getResult();

        $decklists = [];
        foreach ($fellowships as $fellowship) {
            foreach ($fellowship->getDecklists() as $fellowshipDecklist) {
                $decklists[] = $fellowshipDecklist->getDecklist();
            }
        }

        return $decklists;
    }
}
