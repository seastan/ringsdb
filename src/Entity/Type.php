<?php

declare(strict_types=1);

namespace App\Entity;

class Type
{
    /**
     * @var int|null
     */
    private $id;
    /**
     * @var string
     */
    private $code;
    /**
     * @var string
     */
    private $name;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Card>
     */
    private $cards;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->cards = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set code.
     *
     * @param string $code
     */
    public function setCode($code): Type
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name): Type
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Add card.
     */
    public function addCard(Card $card): Type
    {
        $this->cards[] = $card;

        return $this;
    }

    /**
     * Remove card.
     */
    public function removeCard(Card $card): void
    {
        $this->cards->removeElement($card);
    }

    /**
     * Get cards.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Card>
     */
    public function getCards(): \Doctrine\Common\Collections\Collection
    {
        return $this->cards;
    }
}
