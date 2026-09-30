<?php

namespace AppBundle\Model;

/**
 * Base class of Deck and Decklist, which implement the getters used by the exports.
 */
abstract class ExportableDeck {
    /**
     * @return int
     */
    abstract public function getId();

    /**
     * @return string
     */
    abstract public function getName();

    /**
     * @return \DateTime
     */
    abstract public function getDateCreation();

    /**
     * @return \DateTime
     */
    abstract public function getDateUpdate();

    /**
     * @return string
     */
    abstract public function getDescriptionMd();

    /**
     * @return \AppBundle\Entity\User
     */
    abstract public function getUser();

    /**
     * @return string
     */
    abstract public function getVersion();

    /**
     * @return \AppBundle\Entity\Pack|null
     */
    abstract public function getLastPack();

    /**
     * @return \AppBundle\Model\SlotCollectionInterface
     */
    abstract public function getSlots();

    /**
     * @return \AppBundle\Model\SlotCollectionInterface
     */
    abstract public function getSideslots();

    /**
     * @param bool $withUnsavedChanges
     * @return array<string, mixed>
     */
    public function getArrayExport($withUnsavedChanges = false) {
        /* @var $this \AppBundle\Entity\Deck */
        $slots = $this->getSlots();
        $sideslots = $this->getSideslots();
        $last_pack = '';
        if ($this->getLastPack()) {
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
            'last_pack' => $last_pack
        ];
        if (method_exists($this,'getFreezeComments')) {
            $array['freeze_comments'] = $this->getFreezeComments();
        }

        return $array;
    }

    /**
     * @return array<string, mixed>
     */
    public function getTextExport() {
        /* @var $this \AppBundle\Entity\Deck */
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
}