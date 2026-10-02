<?php

declare(strict_types=1);

namespace App\Model;

use ArrayAccess;
use Doctrine\Common\Collections\Collection;
use IteratorAggregate;

/**
 * Interface for a collection of SlotInterface.
 *
 * @template T of SlotInterface
 *
 * @extends \IteratorAggregate<int, T>
 * @extends \ArrayAccess<int, T>
 */
interface SlotCollectionInterface extends \Countable, IteratorAggregate, ArrayAccess
{
    /**
     * Add a slot.
     *
     * @param T $element
     */
    public function add($element): bool;

    /**
     * Remove a slot.
     *
     * @param T $element
     */
    public function removeElement($element): bool;

    /**
     * Get the underlying collection of slots.
     *
     * @return Collection<int, T>
     */
    public function getSlots(): Collection;

    /**
     * Get quantity of cards.
     */
    public function countCards(): int;

    /**
     * Get included packs, by release date: ['pack' => Pack, 'nb' => number of copies of the pack needed].
     *
     * @return array<int, array<string, mixed>>
     */
    public function getIncludedPacks(): array;

    /**
     * Get all slots sorted by type code.
     *
     * @return array<string, list<T>>
     */
    public function getSlotsByType(): array;

    /**
     * Get all slot counts sorted by type code.
     *
     * @return array<string, int>
     */
    public function getCountByType(): array;

    /**
     * Get all slot counts sorted by sphere code.
     *
     * @return non-empty-array<string, int>
     */
    public function getCountBySphere();

    /**
     * Get the hero deck.
     *
     * @return SlotCollectionInterface<T>
     */
    public function getHeroDeck(): SlotCollectionInterface;

    /**
     * Get the draw deck.
     *
     * @return SlotCollectionInterface<T>
     */
    public function getDrawDeck(): SlotCollectionInterface;

    /**
     * Get the content as an array card_code => qty.
     *
     * @return array<int|string, int>
     */
    public function getContent(): array;

    /**
     * Get the starting threat.
     */
    public function getStartingThreat(): int;
}
