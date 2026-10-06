<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\SlotInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="decksideslot")
 */
class Decksideslot implements SlotInterface
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
         * @ORM\ManyToOne(targetEntity="App\Entity\Deck", inversedBy="sideslots")
         * @ORM\JoinColumn(name="deck_id", referencedColumnName="id", nullable=false)
         */
        private Deck $deck,
        /**
         * @ORM\ManyToOne(targetEntity="App\Entity\Card")
         * @ORM\JoinColumn(name="card_id", referencedColumnName="id", nullable=false)
         */
        private Card $card,
        /**
         * @ORM\Column(type="smallint")
         */
        private int $quantity
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
     * Set quantity.
     *
     * @param int $quantity
     */
    public function setQuantity($quantity): Decksideslot
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity.
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * Set deck.
     */
    public function setDeck(Deck $deck): Decksideslot
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

    /**
     * Set card.
     */
    public function setCard(Card $card): Decksideslot
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card.
     */
    public function getCard(): Card
    {
        return $this->card;
    }
}
