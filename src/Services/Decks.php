<?php

declare(strict_types=1);

namespace App\Services;

use App\Entity\CardPrinting;
use App\Repository\DecklistRepository;
use App\Repository\DeckchangeRepository;
use App\Repository\CardRepository;
use App\Entity\Card;
use App\Entity\Deck;
use App\Entity\Deckchange;
use App\Entity\Decksideslot;
use App\Entity\Deckslot;
use App\Helper\DeckValidationHelper;
use App\Repository\CardRepository;
use App\Repository\DeckchangeRepository;
use App\Repository\DecklistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class Decks
{
    /**
     * @var EntityManagerInterface
     */
    private $doctrine;

    /**
     * @var DeckValidationHelper
     */
    private $deck_validation_helper;

    /**
     * @var Diff
     */
    private $diff;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CardRepository
     */
    private $cardRepository;

    /**
     * @var DeckchangeRepository
     */
    private $deckchangeRepository;

    /**
     * @var DecklistRepository
     */
    private $decklistRepository;

    public function __construct(EntityManagerInterface $doctrine, DeckValidationHelper $deck_validation_helper, Diff $diff, LoggerInterface $logger, CardRepository $cardRepository, DeckchangeRepository $deckchangeRepository, DecklistRepository $decklistRepository)
    {
        $this->doctrine = $doctrine;
        $this->deck_validation_helper = $deck_validation_helper;
        $this->diff = $diff;
        $this->logger = $logger;
        $this->cardRepository = $cardRepository;
        $this->deckchangeRepository = $deckchangeRepository;
        $this->decklistRepository = $decklistRepository;
    }

    /**
     * @return array<int, mixed>
     */
    public function getByUser($user)
    {
        /* @var $user \App\Entity\User */
        $decks = $user->getDecks();
        $list = [];

        foreach ($decks as $deck) {
            $list[] = $deck->jsonSerialize(false);
        }

        return $list;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getDecksWithSlotsForUser($user, $limit = null)
    {
        // Step 1: get the right deck IDs with no collection join so LIMIT works correctly
        $idQuery = $this->doctrine->createQuery(
            'SELECT d.id FROM App\Entity\Deck d
             WHERE d.user = :user
             ORDER BY d.dateUpdate DESC, d.id ASC'
        )->setParameter('user', $user);

        if (null !== $limit) {
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
             FROM App\Entity\Deck d
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
                    'version' => $row['major_version'].'.'.$row['minor_version'],
                    'problem' => $row['problem'],
                    'tags' => $row['tags'],
                    'date_creation' => $row['date_creation'] ? new \DateTime($row['date_creation']) : null,
                    'last_pack' => null !== $row['last_pack_name'] ? ['name' => $row['last_pack_name']] : null,
                    'slots' => [],
                    'heroes' => [],
                ];
            }

            if (null !== $row['card_code']) {
                $decks[$deckId]['slots'][$row['card_code']] = (int) $row['qty'];
                if ('hero' === $row['type_code']) {
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
                'SELECT c, p, pk FROM App\Entity\Card c
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

    /**
     * @return int
     */
    public function countDecksForUser($user)
    {
        return (int) $this->doctrine->createQuery(
            'SELECT COUNT(d.id) FROM App\Entity\Deck d WHERE d.user = :user'
        )->setParameter('user', $user)->getSingleScalarResult();
    }

    /**
     * @return Deck
     */
    public function cloneDeck($deck, $user)
    {
        /* @var $deck \App\Entity\Deck */
        if (!$deck) {
            throw new NotFoundHttpException("This deck doesn't exist.");
        }

        $content = [
            'main' => [],
            'side' => [],
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

        /* @var $deck \App\Entity\Deck */
        $deck = new Deck();
        $this->saveDeck($user, $deck, $decklist_id, $name, $description, $tags, $content, null);
        $this->doctrine->flush();

        return $deck;
    }

    /**
     * Normalizes deck tags: a space-separated string or an array of tags becomes a list of
     * distinct, trimmed, non-empty tags.
     *
     * @param string|string[]|null $tags
     *
     * @return string[]
     */
    public function normalizeTags($tags)
    {
        $tags = preg_split('/\s+/', trim(implode(' ', (array) $tags)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_unique($tags));
    }

    public function saveDeck($user, $deck, $decklist_id, $name, $description, $tags, $content, $source_deck)
    {
        /* @var $deck \App\Entity\Deck */
        /* @var $source_deck \App\Entity\Deck */

        if ($decklist_id) {
            /* @var $decklist \App\Entity\Decklist */
            $decklist = $this->decklistRepository->find($decklist_id);
            if ($decklist) {
                $deck->setParent($decklist);
            }
        }

        $deck->setName($name);
        $deck->setDescriptionMd($description);
        $deck->setUser($user);
        $deck->setMinorVersion($deck->getMinorVersion() + 1);

        $cards = [];
        /* @var $latestPack \App\Entity\Pack */
        $latestPack = null;
        $spheres = [];

        foreach ($content['main'] as $card_code => $qty) {
            $card = $this->findCardByCode((string) $card_code);

            if (!$card) {
                continue;
            }

            /* @var $pack \App\Entity\Pack */
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
                } elseif (!$pack->getDateRelease() || $latestPack->getDateRelease() < $pack->getDateRelease()) {
                    $latestPack = $pack;
                }
            }

            $cards[$card_code] = $card;
            if ('hero' == $card->getType()->getCode()) {
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
            $card = $this->findCardByCode((string) $card_code);

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
        $tags = $this->normalizeTags($tags);
        if (empty($tags)) {
            // tags can never be empty. if it is we put spheres in
            $tags = $this->normalizeTags($spheres);
        }

        $deck->setTags(implode(' ', $tags));
        $this->doctrine->persist($deck);

        // on the deck content
        if ($source_deck) {
            // compute diff between current content and saved content
            [$listings] = $this->diff->diffContents([
                $content['main'],
                $source_deck->getSlots()->getContent(),
            ]);

            [$sideListings] = $this->diff->diffContents([
                $content['side'],
                $source_deck->getSideslots()->getContent(),
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
                $change->setVariation((string) json_encode($listings));
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
            if (!isset($cards[$card_code])) {
                continue;
            }
            $card = $cards[$card_code];
            $slot = new Deckslot();
            $slot->setQuantity($qty);
            $slot->setCard($card);
            $slot->setDeck($deck);
            $deck->addSlot($slot);
        }

        foreach ($content['side'] as $card_code => $qty) {
            if (!isset($cards[$card_code])) {
                continue;
            }
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


    /**
     * The card with this code, or else the canonical card of the printing with this image code:
     * deck contents stored as JSON (quest log snapshots...) still use the codes of the cards
     * merged by the card-printings migration.
     */
    private function findCardByCode(string $code): ?Card {
        $card = $this->doctrine->getRepository(Card::class)->findOneBy(['code' => $code]);
        if ($card) {
            return $card;
        }

        $printing = $this->doctrine->getRepository(CardPrinting::class)->findOneBy(['imageCode' => $code]);

        return $printing ? $printing->getCard() : null;
    }

    /**
     * @return void
     */
    public function setSlots(&$deck, $content): void
    {
        /* @var $deck \App\Entity\Deck */
        /* @var $latestPack \App\Entity\Pack */

        $cards = [];
        $latestPack = null;

        foreach ($content['main'] as $card_code => $qty) {
            $card = $this->findCardByCode((string) $card_code);

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
            $card = $this->findCardByCode((string) $card_code);

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
            if (!isset($cards[$card_code])) {
                continue;
            }
            $card = $cards[$card_code];
            $slot = new Deckslot();
            $slot->setQuantity($qty);
            $slot->setCard($card);
            $slot->setDeck($deck);
            $deck->addSlot($slot);
        }

        foreach ($content['side'] as $card_code => $qty) {
            if (!isset($cards[$card_code])) {
                continue;
            }
            $card = $cards[$card_code];
            $slot = new Decksideslot();
            $slot->setQuantity($qty);
            $slot->setCard($card);
            $slot->setDeck($deck);
            $deck->addSideslot($slot);
        }
    }

    public function revertDeck($deck): void
    {
        /* @var $deck \App\Entity\Deck */
        $changes = $this->getUnsavedChanges($deck);

        foreach ($changes as $change) {
            $this->doctrine->remove($change);
        }

        // if deck has only heroes, we delete it
        if (0 === $deck->getSlots()->getDrawDeck()->countCards()) {
            $this->doctrine->remove($deck);
        }
        $this->doctrine->flush();
    }

    /**
     * @return array<int, Deckchange>
     */
    public function getUnsavedChanges($deck)
    {
        return $this->deckchangeRepository->findBy([
            'deck' => $deck,
            'isSaved' => false,
        ]);
    }
}
