<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DeckchangeRepository")
 * @ORM\Table(name="deckchange")
 */
class Deckchange
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     */
    private $dateCreation;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=1024)
     */
    private $variation;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_saved", type="boolean")
     */
    private $isSaved;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=8, nullable=true)
     */
    private $version;

    public function __construct(
        /**
         * @ORM\ManyToOne(targetEntity="App\Entity\Deck", inversedBy="changes")
         * @ORM\JoinColumn(name="deck_id", referencedColumnName="id", nullable=false)
         */
        private Deck $deck
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
