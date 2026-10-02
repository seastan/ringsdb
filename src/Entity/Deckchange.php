<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Deckchange.
 */
class Deckchange
{
    /**
     * @var int
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
    /**
     * @var Deck
     */
    private $deck;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Deckchange
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation.
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set variation.
     *
     * @param string $variation
     *
     * @return Deckchange
     */
    public function setVariation($variation)
    {
        $this->variation = $variation;

        return $this;
    }

    /**
     * Get variation.
     *
     * @return string
     */
    public function getVariation()
    {
        return $this->variation;
    }

    /**
     * Set isSaved.
     *
     * @param bool $isSaved
     *
     * @return Deckchange
     */
    public function setIsSaved($isSaved)
    {
        $this->isSaved = $isSaved;

        return $this;
    }

    /**
     * Get isSaved.
     *
     * @return bool
     */
    public function getIsSaved()
    {
        return $this->isSaved;
    }

    /**
     * Set deck.
     *
     * @return Deckchange
     */
    public function setDeck(Deck $deck)
    {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck.
     *
     * @return Deck
     */
    public function getDeck()
    {
        return $this->deck;
    }

    /**
     * Set version.
     *
     * @param string|null $version
     *
     * @return Deckchange
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }
}
