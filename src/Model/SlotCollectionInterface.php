<?php

declare(strict_types=1);

namespace App\Model;

/**
 * Interface for a collection of SlotInterface.
 *
 * @template T of SlotInterface
 *
 * @extends \IteratorAggregate<int, T>
 * @extends \ArrayAccess<int, T>
 */
interface SlotCollectionInterface extends \Countable, \IteratorAggregate, \ArrayAccess
{
    /**
     * Add a slot.
     *
     * @param T $element
     *
     * @return bool
     */
    public function add($element);

    /**
     * Remove a slot.
     *
     * @param T $element
     *
     * @return bool
     */
    public function removeElement($element);

    /**
     * Get the underlying collection of slots.
     *
     * @return \Doctrine\Common\Collections\Collection<int, T>
     */
    public function getSlots();

    /**
     * Get quantity of cards.
     *
     * @return int
     */
    public function countCards();

    /**
     * Get included packs, by release date: ['pack' => Pack, 'nb' => number of copies of the pack needed].
     *
     * @return array<int, array<string, mixed>>
     */
    public function getIncludedPacks();

    /**
     * Get all slots sorted by type code.
     *
     * @return array<string, list<T>>
     */
    public function getSlotsByType();

    /**
     * Get all slot counts sorted by type code.
     *
     * @return array<string, int>
     */
    public function getCountByType();

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
    public function getHeroDeck();

    /**
     * Get the draw deck.
     *
     * @return SlotCollectionInterface<T>
     */
    public function getDrawDeck();

    /**
     * Get the content as an array card_code => qty.
     *
     * @return array<int|string, int>
     */
    public function getContent();

    /**
     * Get the starting threat.
     *
     * @return int
     */
    public function getStartingThreat();
}
