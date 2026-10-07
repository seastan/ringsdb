<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Deck;
use App\Entity\Decklist;
use App\Entity\Decklistsideslot;
use App\Entity\Decklistslot;
use App\Entity\Pack;
use App\Entity\Sphere;
use App\Helper\DeckValidationHelper;
use App\Repository\SphereRepository;
use App\Services\Texts;

class DecklistFactory
{
    public function __construct(
        private readonly DeckValidationHelper $deckValidationHelper,
        private readonly Texts $texts,
        private readonly SphereRepository $sphereRepository
    ) {
    }

    public function createDecklistFromDeck(Deck $deck, ?string $name = null, ?string $descriptionMd = null): Decklist
    {
        /* @var $lastPack Pack */
        $deck->getLastPack();
        $problem = $this->deckValidationHelper->findProblem($deck, true);
        if ($problem) {
            throw new \Exception('This deck cannot be published  because it is invalid: "'.$this->deckValidationHelper->getProblemLabel($problem).'".');
        }

        // all good for decklist publication

        // increasing deck version
        $deck->setMinorVersion(0);
        $deck->setMajorVersion($deck->getMajorVersion() + 1);

        if (empty($name)) {
            $name = $deck->getName();

            if (empty($name)) {
                $name = 'Untitled Deck';
            }
        }

        $name = substr($name, 0, 60);

        if (empty($descriptionMd)) {
            $descriptionMd = $deck->getDescriptionMd();
        }

        $description = $this->texts->markdown($descriptionMd ?? '');

        $countBySphere = $deck->getSlots()->getCountBySphere();
        $predominantSphere = array_keys($countBySphere, max(...array_values($countBySphere)))[0];
        $predominantSphere = $this->sphereRepository->findOneBy(['code' => $predominantSphere]);

        $heroes = $deck->getSlots()->getHeroDeck();

        $content = [
            'main' => $deck->getSlots()->getContent(),
            'side' => $deck->getSideslots()->getContent(),
        ];
        $new_content = (string) json_encode($content);
        $new_signature = md5($new_content);

        $decklist = new Decklist($deck->getUser());
        $decklist->setName($name);
        $decklist->setVersion($deck->getVersion());
        $decklist->setNameCanonical($this->texts->slugify($name).'-'.$decklist->getVersion());
        $decklist->setDescriptionMd($descriptionMd);
        $decklist->setDescriptionHtml($description);
        $decklist->setSignature($new_signature);
        $decklist->setLastPack($deck->getLastPack());

        foreach ($deck->getSlots() as $slot) {
            $decklistslot = new Decklistslot($decklist, $slot->getCard(), $slot->getQuantity());
            $decklist->getSlots()->add($decklistslot);
        }

        foreach ($deck->getSideslots() as $slot) {
            $decklistslot = new Decklistsideslot($decklist, $slot->getCard(), $slot->getQuantity());
            $decklist->getSideslots()->add($decklistslot);
        }

        $decklist->setPredominantSphere($predominantSphere);
        $decklist->setStartingThreat($decklist->getSlots()->getStartingThreat());

        foreach ($heroes as $hero) {
            if ($hero->getCard()->getSphere() instanceof Sphere) {
                $decklist->addSphere($hero->getCard()->getSphere());
            }
        }

        if (count($deck->getChildren())) {
            $decklist->setPrecedent($deck->getChildren()[0]);
        } elseif ($deck->getParent() instanceof Decklist) {
            $decklist->setPrecedent($deck->getParent());
        }

        $decklist->setParent($deck);

        $deck->setMinorVersion(1);

        return $decklist;
    }
}
