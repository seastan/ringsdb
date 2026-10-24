<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Card;
use App\Entity\Sphere;
use App\Entity\Type;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Decorator for a collection of SlotInterface.
 *
 * @template T of SlotInterface
 *
 * @implements SlotCollectionInterface<T>
 */
class SlotCollectionDecorator implements SlotCollectionInterface
{
    /**
     * @var Collection<int, T>
     */
    protected $slots;

    /**
     * @param Collection<int, T> $slots
     */
    public function __construct(Collection $slots)
    {
        $this->slots = $slots;
    }

    public function add($element): void
    {
        $this->slots->add($element);
    }

    public function removeElement($element): bool
    {
        return $this->slots->removeElement($element);
    }

    public function count(): int
    {
        return $this->slots->count();
    }

    /**
     * @return \Traversable<int, T>
     */
    public function getIterator(): \Traversable
    {
        return $this->slots->getIterator();
    }

    public function offsetExists($offset): bool
    {
        return $this->slots->offsetExists($offset);
    }

    /**
     * @return T|null
     */
    public function offsetGet($offset): mixed
    {
        return $this->slots->offsetGet($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->slots->offsetSet($offset, $value);
    }

    public function offsetUnset($offset): void
    {
        $this->slots->offsetUnset($offset);
    }

    public function countCards(): int
    {
        $count = 0;
        foreach ($this->slots as $slot) {
            $count += $slot->getQuantity();
        }

        return $count;
    }

    public function getIncludedPacks(): array
    {
        $packs = [];
        foreach ($this->slots as $slot) {
            $card = $slot->getCard();
            $pack = $card->getPack();
            if (!$pack) {
                continue;
            }

            if ($pack->getDateRelease()) {
                $pos = $pack->getDateRelease()->format('c');
            } else {
                $pos = 'U';
            }

            $pos .= $pack->getPosition();

            $packs[$pos] ??= [
                'pack' => $pack,
                'nb' => 0,
            ];

            $qty = $card->getQuantity();
            if ($qty) {
                $nbpacks = ceil($slot->getQuantity() / $qty);
                if ($packs[$pos]['nb'] < $nbpacks) {
                    $packs[$pos]['nb'] = $nbpacks;
                }
            }
        }

        ksort($packs);

        return array_values($packs);
    }

    public function getSlotsByType(): array
    {
        $slotsByType = ['hero' => [], 'ally' => [], 'attachment' => [], 'event' => [], 'player-side-quest' => [], 'player-objective' => [], 'contract' => [], 'treasure' => []];
        foreach ($this->slots as $slot) {
            $card = $slot->getCard();
            if ($card->getType() instanceof Type
                && array_key_exists($card->getType()->getCode(), $slotsByType)) {
                $slotsByType[$card->getType()->getCode()][] = $slot;
            }
        }

        return $slotsByType;
    }

    /**
     * @return array<string, int>
     */
    public function getCountByType(): array
    {
        $countByType = ['hero' => 0, 'ally' => 0, 'attachment' => 0, 'event' => 0, 'player-side-quest' => 0, 'player-objective' => 0, 'contract' => 0, 'treasure' => 0];
        foreach ($this->slots as $slot) {
            $card = $slot->getCard();
            if ($card->getType() instanceof Type
                && array_key_exists($card->getType()->getCode(), $countByType)) {
                $countByType[$card->getType()->getCode()] += $slot->getQuantity();
            }
        }

        return $countByType;
    }

    /**
     * @return array<string, int>
     */
    public function getCountBySphere(): array
    {
        $countBySphere = ['spirit' => 0, 'tactics' => 0, 'leadership' => 0, 'lore' => 0];
        foreach ($this->slots as $slot) {
            $card = $slot->getCard();
            if ($card->getSphere() instanceof Sphere
                && array_key_exists($card->getSphere()->getCode(), $countBySphere)) {
                $countBySphere[$card->getSphere()->getCode()] += $slot->getQuantity();
            }
        }

        return $countBySphere;
    }

    public function getHeroDeck(): SlotCollectionInterface
    {
        $heroDeck = [];
        foreach ($this->slots as $slot) {
            $card = $slot->getCard();
            if ($card->getType() instanceof Type && 'hero' === $card->getType()->getCode()) {
                $heroDeck[] = $slot;
            }
        }

        return new SlotCollectionDecorator(new ArrayCollection($heroDeck));
    }

    public function getDrawDeck(): SlotCollectionInterface
    {
        $drawDeck = [];
        foreach ($this->slots as $slot) {
            $card = $slot->getCard();
            if ($card->getType() instanceof Type && in_array($card->getType()->getCode(), ['ally', 'attachment', 'event', 'player-side-quest', 'player-objective', 'contract', 'treasure'], true)) {
                $drawDeck[] = $slot;
            }
        }

        return new SlotCollectionDecorator(new ArrayCollection($drawDeck));
    }

    public function getStartingThreat(): int
    {
        $heroDeck = $this->getHeroDeck();
        $threat = 0;
        $mirlonde = false;
        $folco = false;

        foreach ($heroDeck->getSlots() as $slot) {
            /* @var $card Card */
            $card = $slot->getCard();
            $threat += $card->getThreat();

            if ('Mirlonde' == $card->getName() && $card->getPack() && 'TDF' == $card->getPack()->getCode()) {
                $mirlonde = true;
            }

            if ('Folco Boffin' == $card->getName() && $card->getPack() && 'DoCG' == $card->getPack()->getCode()) {
                $folco = true;
            }
        }

        if ($mirlonde) {
            foreach ($heroDeck->getSlots() as $slot) {
                $card = $slot->getCard();

                if ($card->getSphere() instanceof Sphere && 'lore' === $card->getSphere()->getCode()) {
                    --$threat;
                }
            }
        }

        if ($folco) {
            foreach ($heroDeck->getSlots() as $slot) {
                $card = $slot->getCard();

                if (str_contains((string) $card->getTraits(), 'Hobbit')) {
                    --$threat;
                }
            }
        }

        return $threat;
    }

    /**
     * @return array<string, array{copies: int, deck_limit: int}>
     */
    public function getCopiesAndDeckLimit(): array
    {
        $copiesAndDeckLimit = [];
        foreach ($this->slots as $slot) {
            $card = $slot->getCard();
            $cardName = $card->getName() ?? '';

            if ($card->getType() instanceof Type && 'hero' === $card->getType()->getCode()) {
                $cardName .= 'Hero';
            }

            if (!array_key_exists($cardName, $copiesAndDeckLimit)) {
                $copiesAndDeckLimit[$cardName] = [
                    'copies' => $slot->getQuantity(),
                    'deck_limit' => $card->getDeckLimit(),
                ];
            } else {
                $copiesAndDeckLimit[$cardName]['copies'] += $slot->getQuantity();
                $copiesAndDeckLimit[$cardName]['deck_limit'] = min($card->getDeckLimit(), $copiesAndDeckLimit[$cardName]['deck_limit']);
            }
        }

        return $copiesAndDeckLimit;
    }

    public function getSlots(): Collection
    {
        return $this->slots;
    }

    /**
     * @return array<string, int>
     */
    public function getContent(): array
    {
        $arr = [];
        foreach ($this->slots as $slot) {
            $arr[$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        ksort($arr);

        return $arr;
    }
}
