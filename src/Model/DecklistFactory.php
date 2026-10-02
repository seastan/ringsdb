<?php

declare(strict_types=1);

namespace App\Model;

use App\Entity\Deck;
use App\Entity\Decklist;
use App\Entity\Decklistsideslot;
use App\Entity\Decklistslot;
use App\Helper\DeckValidationHelper;
use App\Repository\SphereRepository;
use App\Services\Texts;

class DecklistFactory
{
    /**
     * @var DeckValidationHelper
     */
    private $deckValidationHelper;

    /**
     * @var Texts
     */
    private $texts;

    /**
     * @var SphereRepository
     */
    private $sphereRepository;

    public function __construct(DeckValidationHelper $deckValidationHelper, Texts $texts, SphereRepository $sphereRepository)
    {
        $this->deckValidationHelper = $deckValidationHelper;
        $this->texts = $texts;
        $this->sphereRepository = $sphereRepository;
    }

    public function createDecklistFromDeck(Deck $deck, $name = null, $descriptionMd = null): Decklist
    {
        /* @var $lastPack \App\Entity\Pack */
        $lastPack = $deck->getLastPack();
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
        $description = $this->texts->markdown($descriptionMd);

        $countBySphere = $deck->getSlots()->getCountBySphere();
        $predominantSphere = array_keys($countBySphere, max($countBySphere))[0];
        $predominantSphere = $this->sphereRepository->findOneBy(['code' => $predominantSphere]);

        $heroes = $deck->getSlots()->getHeroDeck();

        $content = [
            'main' => $deck->getSlots()->getContent(),
            'side' => $deck->getSideslots()->getContent(),
        ];
        $new_content = (string) json_encode($content);
        $new_signature = md5($new_content);

        $decklist = new Decklist();
        $decklist->setName($name);
        $decklist->setVersion($deck->getVersion());
        $decklist->setNameCanonical($this->texts->slugify($name).'-'.$decklist->getVersion());
        $decklist->setDescriptionMd($descriptionMd);
        $decklist->setDescriptionHtml($description);
        $decklist->setDateCreation(new \DateTime());
        $decklist->setDateUpdate(new \DateTime());
        $decklist->setSignature($new_signature);
        $decklist->setLastPack($deck->getLastPack());
        $decklist->setNbVotes(0);
        $decklist->setNbfavorites(0);
        $decklist->setNbcomments(0);
        $decklist->setUser($deck->getUser());

        foreach ($deck->getSlots() as $slot) {
            $decklistslot = new Decklistslot();
            $decklistslot->setQuantity($slot->getQuantity());
            $decklistslot->setCard($slot->getCard());
            $decklistslot->setDecklist($decklist);
            $decklist->getSlots()->add($decklistslot);
        }

        foreach ($deck->getSideslots() as $slot) {
            $decklistslot = new Decklistsideslot();
            $decklistslot->setQuantity($slot->getQuantity());
            $decklistslot->setCard($slot->getCard());
            $decklistslot->setDecklist($decklist);
            $decklist->getSideslots()->add($decklistslot);
        }

        $decklist->setPredominantSphere($predominantSphere);
        $decklist->setStartingThreat($decklist->getSlots()->getStartingThreat());

        foreach ($heroes as $hero) {
            $decklist->addSphere($hero->getCard()->getSphere());
        }

        if (count($deck->getChildren())) {
            $decklist->setPrecedent($deck->getChildren()[0]);
        } elseif ($deck->getParent()) {
            $decklist->setPrecedent($deck->getParent());
        }
        $decklist->setParent($deck);

        $deck->setMinorVersion(1);

        return $decklist;
    }
}
