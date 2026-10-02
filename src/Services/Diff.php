<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\Card;
use App\Model\SlotCollectionInterface;
use App\Model\SlotInterface;

/**
 * Differences between decks.
 *
 * @author AWOPM
 */
class Diff
{
    /**
     * Compares slot collections (the same part of several decks): the cards they have in common,
     * with the smallest of their quantities, then what is left in each collection. The slots are
     * not changed. The cards come in the order of their slots, the common ones in the order of the
     * first collection.
     *
     * @param array<int, SlotCollectionInterface<covariant SlotInterface>> $collections
     *
     * @return array{common: list<array{card: Card, quantity: int}>, differences: list<list<array{card: Card, quantity: int}>>}
     */
    public function compareSlots(array $collections): array
    {
        // for each collection, card code => quantity
        $quantities = [];
        $cards = [];
        foreach ($collections as $slots) {
            $collection = [];
            foreach ($slots as $slot) {
                $card = $slot->getCard();
                $cards[$card->getCode()] = $card;
                $collection[$card->getCode()] = ($collection[$card->getCode()] ?? 0) + $slot->getQuantity();
            }
            $quantities[] = $collection;
        }

        $common = [];
        foreach ($quantities[0] ?? [] as $code => $minimum) {
            foreach ($quantities as $collection) {
                $minimum = min($minimum, $collection[$code] ?? 0);
            }
            if ($minimum > 0) {
                $common[] = ['card' => $cards[$code], 'quantity' => $minimum];
                foreach (array_keys($quantities) as $i) {
                    $quantities[$i][$code] -= $minimum;
                }
            }
        }

        $differences = [];
        foreach ($quantities as $collection) {
            $left = [];
            foreach ($collection as $code => $quantity) {
                if ($quantity > 0) {
                    $left[] = ['card' => $cards[$code], 'quantity' => $quantity];
                }
            }
            $differences[] = $left;
        }

        return ['common' => $common, 'differences' => $differences];
    }

    /**
     * @return array{array<int, array<int|string, int>>, array<int|string, int>}
     */
    public function diffContents($decks)
    {
        // n flat lists of the cards of each decklist
        $ensembles = [];
        foreach ($decks as $deck) {
            $cards = [];
            foreach ($deck as $code => $qty) {
                for ($i = 0; $i < $qty; ++$i) {
                    $cards[] = $code;
                }
            }
            $ensembles[] = $cards;
        }

        // 1 flat list of the cards seen in every decklist
        $conjunction = [];
        for ($i = 0; $i < count($ensembles[0]); ++$i) {
            $code = $ensembles[0][$i];
            $indexes = [$i];
            for ($j = 1; $j < count($ensembles); ++$j) {
                $index = array_search($code, $ensembles[$j]);
                if (false !== $index) {
                    $indexes[] = $index;
                } else {
                    break;
                }
            }
            if (count($indexes) === count($ensembles)) {
                $conjunction[] = $code;
                for ($j = 0; $j < count($indexes); ++$j) {
                    $list = $ensembles[$j];
                    array_splice($list, $indexes[$j], 1);
                    $ensembles[$j] = $list;
                }
                --$i;
            }
        }

        $listings = [];
        for ($i = 0; $i < count($ensembles); ++$i) {
            $listings[$i] = array_count_values($ensembles[$i]);
        }
        $intersect = array_count_values($conjunction);

        return [$listings, $intersect];
    }
}
