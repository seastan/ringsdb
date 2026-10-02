<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Sphere
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
     * @var bool
     */
    private $is_primary;
    /**
     * @var Collection<int, Card>
     */
    private $cards;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->cards = new ArrayCollection();
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
    public function setCode($code): Sphere
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
    public function setName($name): Sphere
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
     * Set isPrimary.
     *
     * @param bool $isPrimary
     */
    public function setIsPrimary($isPrimary): Sphere
    {
        $this->is_primary = $isPrimary;

        return $this;
    }

    /**
     * Get isPrimary.
     */
    public function getIsPrimary(): bool
    {
        return $this->is_primary;
    }

    /**
     * Add card.
     */
    public function addCard(Card $card): Sphere
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
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }
}
