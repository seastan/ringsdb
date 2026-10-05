<?php

declare(strict_types=1);

namespace App\Entity;

class Deckchange
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var \DateTime
     */
    private $dateCreation;

    /**
     * @var string
     */
    private $variation;

    /**
     * @var bool
     */
    private $isSaved;

    /**
     * @var string|null
     */
    private $version;

    private Deck $deck;

    public function __construct(Deck $deck)
    {
        $this->deck = $deck;
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Deckchange
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation.
     */
    public function getDateCreation(): \DateTime
    {
        return $this->dateCreation;
    }

    /**
     * Set variation.
     *
     * @param string $variation
     */
    public function setVariation($variation): Deckchange
    {
        $this->variation = $variation;

        return $this;
    }

    /**
     * Get variation.
     */
    public function getVariation(): string
    {
        return $this->variation;
    }

    /**
     * Set isSaved.
     *
     * @param bool $isSaved
     */
    public function setIsSaved($isSaved): Deckchange
    {
        $this->isSaved = $isSaved;

        return $this;
    }

    /**
     * Get isSaved.
     */
    public function getIsSaved(): bool
    {
        return $this->isSaved;
    }

    /**
     * Set deck.
     */
    public function setDeck(Deck $deck): Deckchange
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
     * Set version.
     *
     * @param string|null $version
     */
    public function setVersion($version): Deckchange
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }
}
