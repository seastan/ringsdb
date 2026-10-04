<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Card;
use App\Entity\Deck;

class DeckValidationHelper
{
    public function __construct()
    {
    }

    public function canIncludeCard(Deck $deck, Card $card): bool
    {
        return true;
    }

    public function findProblem(Deck $deck, bool $casualPlay = false): ?string
    {
        $heroDeck = $deck->getSlots()->getHeroDeck();
        $heroDeckSize = $heroDeck->countCards();

        // Removed check due to Bond of Friendship Contract
        // if ($heroDeckSize > 3) {
        //     return 'too_many_heroes';
        // }

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
        } elseif ($cardsCount < 50 && !$casualPlay) {
            return 'invalid_for_tournament_play';
        }

        foreach ($deck->getSlots()->getCopiesAndDeckLimit() as $value) {
            if ($value['copies'] > $value['deck_limit']) {
                return 'too_many_copies';
            }
        }

        return null;
    }

    public function getProblemLabel(string $problem): string
    {
        if (!$problem) {
            return '';
        }
        $labels = [
            'too_many_heroes' => 'Contains too many heroes',
            'too_few_heroes' => 'Contains too few heroes',
            'too_few_cards' => 'Contains too few cards',
            'too_many_copies' => 'Contains too many copies of a card (by title)',
            'invalid_for_tournament_play' => 'Invalid for tournament play for having less than 50 cards',
            'duplicated_unique_heroes' => 'More than one hero with the same unique name',
            'invalid_cards' => 'Contains forbidden cards',
        ];

        if (isset($labels[$problem])) {
            return $labels[$problem];
        }

        return '';
    }
}
