<?php

namespace AppBundle\Helper;

class FellowshipValidationHelper {
    public function __construct() {
    }

    public function findProblem($fellowship) {
        $heroes = [];

        /* @var $fellowship_decks \AppBundle\Entity\FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        foreach ($fellowship_decks as $fellowship_deck) {
            $deck = $fellowship_deck->getDeck();

            foreach ($deck->getSlots()->getHeroDeck() as &$hero) {
                /* @var $hero \AppBundle\Model\SlotCollectionInterface */
                if (isset($heroes[$hero->getCard()->getName()])) {
                    return 'hero_conflicts';
                }

                $heroes[$hero->getCard()->getName()] = true;
            }
        }

        /* @var $fellowship_decks \AppBundle\Entity\FellowshipDecklist[] */
        $fellowship_decklists = $fellowship->getDecklists();
        foreach ($fellowship_decklists as $fellowship_decklist) {
            $deck = $fellowship_decklist->getDecklist();

            foreach ($deck->getSlots()->getHeroDeck() as &$hero) {
                /* @var $hero \AppBundle\Model\SlotCollectionInterface */
                if (isset($heroes[$hero->getCard()->getName()])) {
                    return 'hero_conflicts';
                }

                $heroes[$hero->getCard()->getName()] = true;
            }
        }

        return null;
    }

    public function getProblemLabel($problem) {
        if (!$problem) {
            return '';
        }
        $labels = [
            'hero_conflicts' => "MHero conflicts between selected decks",
        ];

        if (isset($labels[$problem])) {
            return $labels[$problem];
        }

        return '';
    }

}
