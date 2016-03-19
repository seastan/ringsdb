<?php

namespace AppBundle\Helper;

class DeckValidationHelper {
    public function __construct() {
    }

    public function getInvalidCards($deck) {
        $invalidCards = [];
        foreach ($deck->getSlots() as $slot) {
            if (!$this->canIncludeCard($deck, $slot->getCard())) {
                $invalidCards[] = $slot->getCard();
            }
        }

        return $invalidCards;
    }

    public function canIncludeCard($deck, $card) {
        return true;
    }

    public function findProblem($deck) {
        $heroDeck = $deck->getSlots()->getHeroDeck();
        $heroDeckSize = $heroDeck->countCards();

        if ($heroDeckSize > 3) {
            return 'too_many_heroes';
        }

        if ($heroDeckSize < 1) {
            return 'too_few_heroes';
        }

        $heroes = [];
        foreach ($heroDeck as $hero) {
            if (isset($heroes[$hero->getCard()->getName()])) {
                return 'duplicated_unique_heroes';
            }

            $heroes[$hero->getCard()->getName()] = true;
        }

        if ($deck->getSlots()->getDrawDeck()->countCards() < 50) {
            return 'too_few_cards';
        }

        if (!empty($this->getInvalidCards($deck))) {
            return 'invalid_cards';
        }
        return null;
    }

    public function getProblemLabel($problem) {
        if (!$problem) {
            return '';
        }
        $labels = [
            'too_many_heroes' => "Contains too many Heroes",
            'too_few_heroes' => "Contains too few Heroes",
            'too_few_cards' => "Contains too few cards",
            'duplicated_unique_heroes' => "More than one hero with the same unique name",
            'invalid_cards' => "Contains forbidden cards"
        ];

        if (isset($labels[$problem])) {
            return $labels[$problem];
        }

        return '';
    }
}