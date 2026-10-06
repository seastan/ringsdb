<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FellowshipDeck.
 *
 * @ORM\Entity
 * @ORM\Table(name="fellowship_deck")
 */
class FellowshipDeck
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    public function __construct(
        /**
         * @ORM\ManyToOne(targetEntity="App\Entity\Fellowship", inversedBy="decks")
         * @ORM\JoinColumn(name="fellowship_id", referencedColumnName="id", nullable=false)
         */
        private Fellowship $fellowship,
        /**
         * @ORM\ManyToOne(targetEntity="App\Entity\Deck", inversedBy="fellowships")
         * @ORM\JoinColumn(name="deck_id", referencedColumnName="id", nullable=false)
         */
        private Deck $deck,
        /**
         * @ORM\Column(name="deck_number", type="smallint")
         */
        private int $deckNumber
    ) {
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set deckNumber.
     */
    public function setDeckNumber(int $deckNumber): FellowshipDeck
    {
        $this->deckNumber = $deckNumber;

        return $this;
    }

    /**
     * Get deckNumber.
     */
    public function getDeckNumber(): int
    {
        return $this->deckNumber;
    }

    /**
     * Set fellowship.
     */
    public function setFellowship(Fellowship $fellowship): FellowshipDeck
    {
        $this->fellowship = $fellowship;

        return $this;
    }

    /**
     * Get fellowship.
     */
    public function getFellowship(): Fellowship
    {
        return $this->fellowship;
    }

    /**
     * Set deck.
     */
    public function setDeck(Deck $deck): FellowshipDeck
    {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck.
     */
    public function getDeck(): Deck
    {
        return $this->deck;
    }
}
