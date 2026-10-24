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

    /**
     * @return TwigTest[]
     */
    public function getTests()
    {
        // Named-method callables, not closures: on PHP 8.4 a closure stringifies as
        // "{closure:file:line}" (with colons), and Twig 2.x compiles a closure-based
        // test using that name, producing invalid PHP in the cached template.
        return [
            new TwigTest('decklist', [$this, 'isDecklist']),
            new TwigTest('deck', [$this, 'isDeck']),
        ];
    }

    public function isDecklist(mixed $event): bool
    {
        return $event instanceof Decklist;
    }

    public function isDeck(mixed $event): bool
    {
        return $event instanceof Deck;
    }
}
