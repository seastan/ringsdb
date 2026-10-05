<?php

declare(strict_types=1);

namespace App\Entity;

class UserCustomPackCard
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var UserCustomPack
     */
    private $customPack;

    /**
     * @var Card
     */
    private $card;

    /**
     * @var int
     */
    private $quantity = 1;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCustomPack(): UserCustomPack
    {
        return $this->customPack;
    }

    /**
     * @return $this
     */
    public function setCustomPack(UserCustomPack $customPack)
    {
        $this->customPack = $customPack;

        return $this;
    }

    public function getCard(): Card
    {
        return $this->card;
    }

    /**
     * @return $this
     */
    public function setCard(Card $card)
    {
        $this->card = $card;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return $this
     */
    public function setQuantity(int $quantity)
    {
        $this->quantity = max(1, $quantity);

        return $this;
    }
}
