<?php

namespace AppBundle\Helper;

use AppBundle\Entity\Deck;
use AppBundle\Entity\Decklist;

class TwigExtension extends \Twig_Extension {
    public function getName() {
        return "Twig instance of";
    }

    public function getTests() {
        return [
            new \Twig_SimpleTest('decklist', function($event) {
                return $event instanceof Decklist;
            }),
            new \Twig_SimpleTest('deck', function($event) {
                return $event instanceof Deck;
            })
        ];
    }
}