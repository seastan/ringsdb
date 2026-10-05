<?php

declare(strict_types=1);

namespace App\Entity;

class UserCustomPackCard
{
    /**
     * @var int|null
     */
    private $id;

    private UserCustomPack $customPack;

    private Card $card;

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
