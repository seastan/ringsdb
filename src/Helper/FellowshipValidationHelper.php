<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Fellowship;
use App\Entity\FellowshipDeck;
use App\Entity\FellowshipDecklist;
use App\Model\SlotCollectionInterface;
use App\Model\SlotInterface;

class FellowshipValidationHelper
{
    public function findProblem(Fellowship $fellowship): ?string
    {
        $heroes = [];
        $count = 0;

        /* @var $fellowship_decks FellowshipDeck[] */
        $fellowship_decks = $fellowship->getDecks();
        foreach ($fellowship_decks as $fellowship_deck) {
            ++$count;
            $deck = $fellowship_deck->getDeck();

            foreach ($deck->getSlots()->getHeroDeck() as &$hero) {
                /* @var $hero SlotCollectionInterface<covariant SlotInterface> */
                if (isset($heroes[$hero->getCard()->getName()])) {
                    return 'hero_conflicts';
                }

                $heroes[$hero->getCard()->getName()] = true;
            }
        }

        /* @var $fellowship_decks FellowshipDecklist[] */
        $fellowship_decklists = $fellowship->getDecklists();
        foreach ($fellowship_decklists as $fellowship_decklist) {
            ++$count;
            $deck = $fellowship_decklist->getDecklist();

            foreach ($deck->getSlots()->getHeroDeck() as &$hero) {
                /* @var $hero SlotCollectionInterface<covariant SlotInterface> */
                if (isset($heroes[$hero->getCard()->getName()])) {
                    return 'hero_conflicts';
                }

                $heroes[$hero->getCard()->getName()] = true;
            }
        }

        if (0 === $count) {
            return 'too_few_decks';
        }

        return null;
    }

    public function getProblemLabel(string $problem): string
    {
        if (!$problem) {
            return '';
        }

        $labels = [
            'too_few_decks' => 'Too few decks selectsd',
            'hero_conflicts' => 'MHero conflicts between selected decks',
        ];

        return $labels[$problem] ?? '';
    }
}
