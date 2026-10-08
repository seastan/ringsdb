<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\SlotInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'decklistslot')]
class Decklistslot implements SlotInterface
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

    #[ORM\ManyToOne(targetEntity: Decklist::class, inversedBy: 'slots')]
    #[ORM\JoinColumn(name: 'decklist_id', referencedColumnName: 'id', nullable: false)]
    private Decklist $decklist;

    #[ORM\ManyToOne(targetEntity: Card::class)]
    #[ORM\JoinColumn(name: 'card_id', referencedColumnName: 'id', nullable: false)]
    private Card $card;

    public function __construct(Decklist $decklist, Card $card, int $quantity)
    {
        $this->decklist = $decklist;
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
    public function setQuantity($quantity): Decklistslot
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
     * Set decklist.
     */
    public function setDecklist(Decklist $decklist): Decklistslot
    {
        $this->decklist = $decklist;

        return $this;
    }

    /**
     * Get decklist.
     */
    public function getDecklist(): Decklist
    {
        return $this->decklist;
    }

    /**
     * Set card.
     */
    public function setCard(Card $card): Decklistslot
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
