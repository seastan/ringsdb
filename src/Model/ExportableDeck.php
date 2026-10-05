<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Deck;
use App\Entity\Pack;
use App\Entity\User;

/**
 * Base class of Deck and Decklist, which implement the getters used by the exports.
 */
abstract class ExportableDeck
{
    abstract public function getId(): ?int;

    abstract public function getName(): string;

    abstract public function getDateCreation(): \DateTime;

    abstract public function getDateUpdate(): \DateTime;

    abstract public function getDescriptionMd(): ?string;

    abstract public function getUser(): User;

    abstract public function getVersion(): string;

    abstract public function getLastPack(): ?Pack;

    /**
     * @return SlotCollectionInterface<covariant \App\Model\SlotInterface>
     */
    abstract public function getSlots(): SlotCollectionInterface;

    /**
     * @return SlotCollectionInterface<covariant \App\Model\SlotInterface>
     */
    abstract public function getSideslots(): SlotCollectionInterface;

    /**
     * @param bool $withUnsavedChanges
     *
     * @return array<string, mixed>
     */
    public function getArrayExport($withUnsavedChanges = false): array
    {
        /* @var $this Deck */
        $slots = $this->getSlots();
        $sideslots = $this->getSideslots();
        $last_pack = '';
        if ($this->getLastPack() instanceof Pack) {
            $last_pack = $this->getLastPack()->getName();
        }

        $array = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'date_creation' => $this->getDateCreation()->format('c'),
            'date_update' => $this->getDateUpdate()->format('c'),
            'description_md' => $this->getDescriptionMd(),
            'user_id' => $this->getUser()->getId(),
            'heroes' => $slots->getHeroDeck()->getContent(),
            'slots' => $slots->getContent(),
            'sideslots' => $sideslots->getContent(),
            'version' => $this->getVersion(),
            'last_pack' => $last_pack,
        ];
        if (method_exists($this, 'getFreezeComments')) {
            $array['freeze_comments'] = $this->getFreezeComments();
        }

        return $array;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTextExport(): array
    {
        /* @var $this Deck */
        $slots = $this->getSlots();
        $sideslots = $this->getSideslots();

        return [
            'name' => $this->getName(),
            'draw_deck_size' => $slots->getDrawDeck()->countCards(),
            'hero_deck_size' => $slots->getHeroDeck()->countCards(),
            'included_packs' => $slots->getIncludedPacks(),
            'slots_by_type' => $slots->getSlotsByType(),
            'has_sideboard' => count($sideslots) > 0,
            'sideslots_by_type' => $sideslots->getSlotsByType(),
        ];
    }

    /**
     * @return array{main: array<string, int>, side: array<string, int>}
     */
    public function getContent(): array
    {
        $content = [
            'main' => [],
            'side' => [],
        ];

        foreach ($this->getSlots() as $slot) {
            $content['main'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        foreach ($this->getSideslots() as $slot) {
            $content['side'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        return $content;
    }
}
