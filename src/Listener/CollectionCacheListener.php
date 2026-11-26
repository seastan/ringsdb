<?php

declare(strict_types=1);

namespace App\Listener;

use App\Entity\Card;
use App\Entity\CardPrinting;
use App\Entity\Cycle;
use App\Entity\Pack;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;

/**
 * Evicts the cached inverse-side collections (Card.printings, Pack.printings, Cycle.packs) when a
 * printing or a pack is inserted, updated or deleted: Doctrine updates the second-level cache of a
 * collection only when the collection itself changes, not when the owning side does.
 */
#[AsDoctrineListener(event: Events::onFlush)]
#[AsDoctrineListener(event: Events::postFlush)]
class CollectionCacheListener
{
    /**
     * @var array<string, array{class-string, string, Card|Pack|Cycle}> the collections to evict
     *                                                                  after the flush
     */
    private array $collections = [];

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        $entities = [
            ...$unitOfWork->getScheduledEntityInsertions(),
            ...$unitOfWork->getScheduledEntityUpdates(),
            ...$unitOfWork->getScheduledEntityDeletions(),
        ];
        foreach ($entities as $entity) {
            // the new owner, and the previous one if it changed
            $changeSet = $unitOfWork->getEntityChangeSet($entity);
            if ($entity instanceof CardPrinting) {
                $this->add(Card::class, 'printings', $entity->getCard(), $changeSet['card'][0] ?? null);
                $this->add(Pack::class, 'printings', $entity->getPack(), $changeSet['pack'][0] ?? null);
            } elseif ($entity instanceof Pack) {
                $this->add(Cycle::class, 'packs', $entity->getCycle(), $changeSet['cycle'][0] ?? null);
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $cache = $args->getObjectManager()->getCache();
        $collections = $this->collections;
        $this->collections = [];
        if (null === $cache) {
            return;
        }

        foreach ($collections as [$class, $association, $owner]) {
            if (null !== $owner->getId()) {
                $cache->evictCollection($class, $association, $owner->getId());
            }
        }
    }

    /**
     * @param class-string $class
     */
    private function add(string $class, string $association, Card|Pack|Cycle|null ...$owners): void
    {
        foreach ($owners as $owner) {
            if (null !== $owner) {
                $this->collections[$class.'.'.$association.'#'.spl_object_id($owner)] = [$class, $association, $owner];
            }
        }
    }
}
