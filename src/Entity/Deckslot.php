<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\SlotInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'deckslot')]
class Deckslot implements SlotInterface
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(type: 'smallint')]
    private int $quantity;

    #[ORM\ManyToOne(targetEntity: Deck::class, inversedBy: 'slots')]
    #[ORM\JoinColumn(name: 'deck_id', referencedColumnName: 'id', nullable: false)]
    private Deck $deck;

    #[ORM\ManyToOne(targetEntity: Card::class)]
    #[ORM\JoinColumn(name: 'card_id', referencedColumnName: 'id', nullable: false)]
    private Card $card;

    public function __construct(Deck $deck, Card $card, int $quantity)
    {
        $this->deck = $deck;
        $this->card = $card;
        $this->quantity = $quantity;
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
    public function setQuantity($quantity): Deckslot
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
    public function setDeck(Deck $deck): Deckslot
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
    public function setCard(Card $card): Deckslot
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
