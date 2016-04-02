<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deck;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Deckslot;
use AppBundle\Entity\Decksideslot;
use Symfony\Bridge\Monolog\Logger;
use AppBundle\Entity\Deckchange;
use AppBundle\Helper\DeckValidationHelper;
use AppBundle\Helper\AgendaHelper;

class Decks {
    public function __construct(EntityManager $doctrine, DeckValidationHelper $deck_validation_helper, Diff $diff, Logger $logger) {
        $this->doctrine = $doctrine;
        $this->deck_validation_helper = $deck_validation_helper;
        $this->diff = $diff;
        $this->logger = $logger;
    }

    public function getByUser($user, $decode_variation = false) {
        $decks = $user->getDecks();
        $list = [];

        foreach ($decks as $deck) {
            $list[] = $deck->jsonSerialize(false);
        }

        return $list;
    }

    public function cloneDeck($deck, $user) {
        /* @var $deck \AppBundle\Entity\Deck */
        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $content = [
            'main' => [],
            'side' => []
        ];

        foreach ($deck->getSlots() as $slot) {
            $content['main'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        foreach ($deck->getSideslots() as $slot) {
            $content['side'][$slot->getCard()->getCode()] = $slot->getQuantity();
        }

        $name = $deck->getName();
        $description = $deck->getDescriptionMd();
        $decklist_id = $deck->getParent() ? $deck->getParent()->getId() : null;
        $tags = '';

        if (empty($name)) {
            $name = 'Untitled Deck';
        }

        /* @var $deck \AppBundle\Entity\Deck */
        $deck = new Deck();
        $this->saveDeck($user, $deck, $decklist_id, $name, $description, $tags, $content, null);
        $this->doctrine->flush();
        return $deck;
    }

    public function saveDeck($user, $deck, $decklist_id, $name, $description, $tags, $content, $source_deck) {
        /* @var $deck \AppBundle\Entity\Deck */
        if ($decklist_id) {
            $decklist = $this->doctrine->getRepository('AppBundle:Decklist')->find($decklist_id);
            if ($decklist) {
                $deck->setParent($decklist);
            }
        }

        $deck->setName($name);
        $deck->setDescriptionMd($description);
        $deck->setUser($user);
        $deck->setMinorVersion($deck->getMinorVersion() + 1);

        $cards = [];
        /* @var $latestPack \AppBundle\Entity\Pack */
        $latestPack = null;
        $spheres = [];

        foreach ($content['main'] as $card_code => $qty) {
            $card = $this->doctrine->getRepository('AppBundle:Card')->findOneBy([
                "code" => $card_code
            ]);

            if (!$card) {
                continue;
            }

            $pack = $card->getPack();
            if (!$latestPack) {
                $latestPack = $pack;
            } else {
                if ($latestPack->getCycle()->getPosition() < $pack->getCycle()->getPosition()) {
                    $latestPack = $pack;
                } else {
                    if ($latestPack->getCycle()->getPosition() == $pack->getCycle()->getPosition() && $latestPack->getPosition() < $pack->getPosition()) {
                        $latestPack = $pack;
                    }
                }
            }

            $cards[$card_code] = $card;
            if ($card->getType()->getCode() == 'hero') {
                $spheres[] = $card->getSphere()->getCode();
            }

            if ($qty > $card->getDeckLimit()) {
                if (is_array($content['main'])) {
                    $content['main'][$card_code] = $card->getDeckLimit();
                } else {
                    $content['main']->$card_code = $card->getDeckLimit();
                }
            }
        }

        foreach ($content['side'] as $card_code => $qty) {
            $card = $this->doctrine->getRepository('AppBundle:Card')->findOneBy([
                "code" => $card_code
            ]);

            if (!$card) {
                continue;
            }

            $cards[$card_code] = $card;

            if ($qty > $card->getDeckLimit()) {
                if (is_array($content['side'])) {
                    $content['side'][$card_code] = $card->getDeckLimit();
                } else {
                    $content['side']->$card_code = $card->getDeckLimit();
                }
            }
        }

        $deck->setLastPack($latestPack);
        if (empty ($tags)) {
            // tags can never be empty. if it is we put spheres in
            $tags = $spheres;
        }

        if (is_string($tags)) {
            $tags = preg_split('/\s+/', $tags);
        }

        $tags = implode(' ', array_unique(array_values($tags)));
        $deck->setTags($tags);
        $this->doctrine->persist($deck);

        // on the deck content
        if ($source_deck) {
            // compute diff between current content and saved content
            list ($listings) = $this->diff->diffContents([
                $content['main'],
                $source_deck->getSlots()->getContent()
            ]);

            list ($sideListings) = $this->diff->diffContents([
                $content['side'],
                $source_deck->getSideslots()->getContent()
            ]);

            $listings[2] = $sideListings[0];
            $listings[3] = $sideListings[1];

            // remove all change (autosave) since last deck update (changes are sorted)
            $changes = $this->getUnsavedChanges($deck);
            foreach ($changes as $change) {
                $this->doctrine->remove($change);
            }

            $this->doctrine->flush();
            // save new change unless empty
            if (count($listings[0]) || count($listings[1]) || count($listings[2]) || count($listings[3])) {
                $change = new Deckchange();
                $change->setDeck($deck);
                $change->setVariation(json_encode($listings));
                $change->setIsSaved(true);
                $change->setVersion($deck->getVersion());
                $this->doctrine->persist($change);
                $this->doctrine->flush();
            }

            // copy version
            $deck->setMajorVersion($source_deck->getMajorVersion());
            $deck->setMinorVersion($source_deck->getMinorVersion());
        }

        foreach ($deck->getSlots() as $slot) {
            $deck->removeSlot($slot);
            $this->doctrine->remove($slot);
        }

        foreach ($deck->getSideslots() as $slot) {
            $deck->removeSideslot($slot);
            $this->doctrine->remove($slot);
        }

        foreach ($content['main'] as $card_code => $qty) {
            $card = $cards[$card_code];
            $slot = new Deckslot();
            $slot->setQuantity($qty);
            $slot->setCard($card);
            $slot->setDeck($deck);
            $deck->addSlot($slot);
        }

        foreach ($content['side'] as $card_code => $qty) {
            $card = $cards[$card_code];
            $slot = new Decksideslot();
            $slot->setQuantity($qty);
            $slot->setCard($card);
            $slot->setDeck($deck);
            $deck->addSideslot($slot);
        }

        $deck->setProblem($this->deck_validation_helper->findProblem($deck));

        return $deck->getId();
    }

    public function revertDeck($deck) {
        $changes = $this->getUnsavedChanges($deck);

        foreach ($changes as $change) {
            $this->doctrine->remove($change);
        }

        // if deck has only heroes, we delete it
        if ($deck->getSlots()->getDrawDeck()->countCards() === 0) {
            $this->doctrine->remove($deck);
        }
        $this->doctrine->flush();
    }

    public function getUnsavedChanges($deck) {
        return $this->doctrine->getRepository('AppBundle:Deckchange')->findBy([
            'deck' => $deck,
            'isSaved' => false
        ]);
    }
}
