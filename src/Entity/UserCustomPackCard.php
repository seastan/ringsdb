<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'user_custom_pack_card')]
#[ORM\Index(name: 'fk_ucpc_card', columns: ['card_id'])]
#[ORM\UniqueConstraint(name: 'ucpc_pack_card_idx', columns: ['custom_pack_id', 'card_id'])]
class UserCustomPackCard
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\ManyToOne(targetEntity: UserCustomPack::class, inversedBy: 'cards')]
    #[ORM\JoinColumn(name: 'custom_pack_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private UserCustomPack $customPack;

    #[ORM\ManyToOne(targetEntity: Card::class)]
    #[ORM\JoinColumn(name: 'card_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Card $card;

    #[ORM\Column(type: 'smallint', nullable: false, options: ['default' => 1, 'unsigned' => true])]
    public int $quantity;

    public function __construct(UserCustomPack $customPack, Card $card, int $quantity)
    {
        $this->customPack = $customPack;
        $this->card = $card;
        $this->quantity = $quantity;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomPack(): UserCustomPack
    {
        return $this->customPack;
    }

    public function setCustomPack(UserCustomPack $customPack): self
    {
        $this->customPack = $customPack;

        return $this;
    }

    public function getCard(): Card
    {
        return $this->card;
    }

    public function setCard(Card $card): self
    {
        $this->card = $card;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): self
    {
        $this->quantity = max(1, $quantity);

        return $this;
    }
}
