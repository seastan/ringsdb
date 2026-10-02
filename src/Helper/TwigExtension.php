<?php

declare(strict_types=1);

namespace App\Helper;

use App\Entity\Deck;
use App\Entity\Decklist;
use Twig\Extension\AbstractExtension;
use Twig\TwigTest;

class TwigExtension extends AbstractExtension
{
    public function getName(): string
    {
        return 'Twig instance of';
    }

    public function getTests()
    {
        return [
            new TwigTest('decklist', fn ($event) => $event instanceof Decklist),
            new TwigTest('deck', fn ($event) => $event instanceof Deck),
        ];
    }
}
