<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deck;
use Doctrine\ORM\EntityManager;
use AppBundle\Entity\Deckslot;
use AppBundle\Entity\Decksideslot;
use Symfony\Bridge\Monolog\Logger;
use AppBundle\Entity\Deckchange;
use AppBundle\Helper\DeckValidationHelper;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Decks {
    public function __construct(EntityManager $doctrine, DeckValidationHelper $deck_validation_helper, Diff $diff, Logger $logger) {
        $this->doctrine = $doctrine;
        $this->deck_validation_helper = $deck_validation_helper;
        $this->diff = $diff;
        $this->logger = $logger;
    }

    public function getByUser($user) {
        /* @var $user \AppBundle\Entity\User */
        $decks = $user->getDecks();
        $list = [];

        foreach ($decks as $deck) {
            $list[] = $deck->jsonSerialize(false);
        }

        return $list;
    }

    public function getDecksWithSlotsForUser($user, $limit = null) {
        // Step 1: get the right deck IDs with no collection join so LIMIT works correctly
        $idQuery = $this->doctrine->createQuery(
            'SELECT d.id FROM AppBundle\Entity\Deck d
             WHERE d.user = :user
             ORDER BY d.dateUpdate DESC'
        )->setParameter('user', $user);

        if ($limit !== null) {
            $idQuery->setMaxResults($limit);
        }

        $ids = array_column($idQuery->getScalarResult(), 'id');

        if (empty($ids)) {
            return [];
        }

        // Step 2: pull only the scalar columns the deck list needs, one row per slot.
        // Hydrating the full Card graph for every slot (tens of thousands of rows for
        // users with many decks) blew the PHP memory limit, so we avoid entity
        // hydration here and rebuild the lightweight per-deck structure by hand.
        $rows = $this->doctrine->createQuery(
            'SELECT d.id AS deck_id, d.name AS name,
                    d.majorVersion AS major_version, d.minorVersion AS minor_version,
                    d.problem AS problem, d.tags AS tags, d.dateCreation AS date_creation,
                    lp.name AS last_pack_name,
                    s.quantity AS qty, c.code AS card_code, ct.code AS type_code
             FROM AppBundle\Entity\Deck d
             LEFT JOIN d.lastPack lp
             LEFT JOIN d.slots s
             LEFT JOIN s.card c
             LEFT JOIN c.type ct
             WHERE d.id IN (:ids)
             ORDER BY d.dateUpdate DESC, d.id ASC'
        )->setParameter('ids', $ids)->getScalarResult();

        $decks = [];
        $heroCodes = [];

        foreach ($rows as $row) {
            $deckId = $row['deck_id'];

            if (!isset($decks[$deckId])) {
                $decks[$deckId] = [
                    'id' => (int) $deckId,
                    'name' => $row['name'],
                    'version' => $row['major_version'] . '.' . $row['minor_version'],
                    'problem' => $row['problem'],
                    'tags' => $row['tags'],
                    'date_creation' => $row['date_creation'] ? new \DateTime($row['date_creation']) : null,
                    'last_pack' => $row['last_pack_name'] !== null ? ['name' => $row['last_pack_name']] : null,
                    'slots' => [],
                    'heroes' => [],
                ];
            }

            if ($row['card_code'] !== null) {
                $decks[$deckId]['slots'][$row['card_code']] = (int) $row['qty'];
                if ($row['type_code'] === 'hero') {
                    // remember hero codes; the Card entities are bulk-loaded below
                    $decks[$deckId]['heroes'][$row['card_code']] = true;
                    $heroCodes[$row['card_code']] = true;
                }
            }
        }

        // Load the (small, bounded) set of distinct hero cards as real entities so the
        // template's hero.sphere.code / hero.pack.code accessors keep working unchanged.
        $heroCards = [];
        if (!empty($heroCodes)) {
            $heroEntities = $this->doctrine->createQuery(
                'SELECT c, p, pk FROM AppBundle\Entity\Card c
                 LEFT JOIN c.printings p
                 LEFT JOIN p.pack pk
                 WHERE c.code IN (:codes)'
            )->setParameter('codes', array_keys($heroCodes))->getResult();

            foreach ($heroEntities as $heroCard) {
                $heroCards[$heroCard->getCode()] = $heroCard;
            }
        }

        foreach ($decks as &$deck) {
            ksort($deck['slots']);

            $heroes = [];
            foreach (array_keys($deck['heroes']) as $code) {
                if (isset($heroCards[$code])) {
                    $heroes[] = $heroCards[$code];
                }
            }
            $deck['heroes'] = $heroes;
        }
        unset($deck);

        return array_values($decks);
    }

    public function countDecksForUser($user) {
        return (int) $this->doctrine->createQuery(
            'SELECT COUNT(d.id) FROM AppBundle\Entity\Deck d WHERE d.user = :user'
        )->setParameter('user', $user)->getSingleScalarResult();
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
        /* @var $source_deck \AppBundle\Entity\Deck */

        if ($decklist_id) {
            /* @var $decklist \AppBundle\Entity\Decklist */
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

            /* @var $pack \AppBundle\Entity\Pack */
            $pack = $card->getPack();
            if (!$latestPack) {
                $latestPack = $pack;
            } else {
                if (!$latestPack->getDateRelease() && !$pack->getDateRelease()) {
                    if ($latestPack->getCycle()->getPosition() < $pack->getCycle()->getPosition()) {
                        $latestPack = $pack;
                    } else {
                        if ($latestPack->getCycle()->getPosition() == $pack->getCycle()->getPosition() && $latestPack->getPosition() < $pack->getPosition()) {
                            $latestPack = $pack;
                        }
                    }
                } else if (!$pack->getDateRelease() || $latestPack->getDateRelease() < $pack->getDateRelease()) {
                    $latestPack = $pack;
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


    public function setSlots(&$deck, $content) {
        /* @var $deck \AppBundle\Entity\Deck */
        /* @var $latestPack \AppBundle\Entity\Pack */

        $cards = [];
        $latestPack = null;

        foreach ($content['main'] as $card_code => $qty) {
            $card = $this->doctrine->getRepository('AppBundle:Card')->findOneBy([
                "code" => $card_code
            ]);

            if (!$card) {
                continue;
            }

            $cards[$card_code] = $card;

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
    }

    public function revertDeck($deck) {
        /* @var $deck \AppBundle\Entity\Deck */
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
