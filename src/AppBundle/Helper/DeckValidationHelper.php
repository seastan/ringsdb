<?php

namespace AppBundle\Helper;

class DeckValidationHelper {
    public function __construct() {
    }

    public function getInvalidCards($deck) {
        $invalidCards = [];

        /*
        foreach ($deck->getSlots() as $slot) {
            if (!$this->canIncludeCard($deck, $slot->getCard())) {
                $invalidCards[] = $slot->getCard();
            }
        }
        */

        return $invalidCards;
    }

    public function canIncludeCard($deck, $card) {
        return true;
    }

    public function findProblem($deck, $casualPlay = false) {
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

        $cardsCount = $deck->getSlots()->getDrawDeck()->countCards();
        if ($cardsCount < 30) {
            return 'too_few_cards';
        } else if ($cardsCount < 50 && !$casualPlay) {
            return 'invalid_for_tournament_play';
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
            'invalid_for_tournament_play' => "Invalid for tournament play for having less than 50 cards",
            'duplicated_unique_heroes' => "More than one hero with the same unique name",
            'invalid_cards' => "Contains forbidden cards"
        ];

        if (isset($labels[$problem])) {
            return $labels[$problem];
        }

        return '';
    }
}
