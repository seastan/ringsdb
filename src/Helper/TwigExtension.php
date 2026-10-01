<?php

namespace App\Helper;

use App\Entity\Deck;
use App\Entity\Decklist;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class TwigExtension extends AbstractExtension
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'Twig instance of';
    }

    public function getTests()
    {
        return [
            new TwigTest('decklist', function ($event) {
                return $event instanceof Decklist;
            }),
            new TwigTest('deck', function ($event) {
                return $event instanceof Deck;
            }),
        ];
    }
}
