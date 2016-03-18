<?php

namespace AppBundle\Model;

class ExportableDeck {
    public function getArrayExport($withUnsavedChanges = false) {
        $slots = $this->getSlots();
        $array = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'date_creation' => $this->getDateCreation()->format('c'),
            'date_update' => $this->getDateUpdate()->format('c'),
            'description_md' => $this->getDescriptionMd(),
            'user_id' => $this->getUser()->getId(),
            'slots' => $slots->getContent(),
            'version' => $this->getVersion(),
        ];

        return $array;
    }

    public function getTextExport() {
        $slots = $this->getSlots();

        return [
            'name' => $this->getName(),
            'draw_deck_size' => $slots->getDrawDeck()->countCards(),
            'hero_deck_size' => $slots->getHeroDeck()->countCards(),
            'included_packs' => $slots->getIncludedPacks(),
            'slots_by_type' => $slots->getSlotsByType()
        ];
    }
}