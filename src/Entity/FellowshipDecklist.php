<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * FellowshipDecklist.
 */
#[ORM\Entity]
#[ORM\Table(name: 'fellowship_decklist')]
class FellowshipDecklist
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var int
     */
    #[ORM\Column(name: 'deck_number', type: 'smallint')]
    private $deckNumber;

    #[ORM\ManyToOne(targetEntity: Fellowship::class, inversedBy: 'decklists')]
    #[ORM\JoinColumn(name: 'fellowship_id', referencedColumnName: 'id', nullable: false)]
    private Fellowship $fellowship;

    #[ORM\ManyToOne(targetEntity: Decklist::class, inversedBy: 'fellowships')]
    #[ORM\JoinColumn(name: 'decklist_id', referencedColumnName: 'id', nullable: false)]
    private Decklist $decklist;

    public function __construct(Decklist $decklist, Fellowship $fellowship)
    {
        $this->decklist = $decklist;
        $this->fellowship = $fellowship;
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set deckNumber.
     *
     * @param int $deckNumber
     */
    public function setDeckNumber($deckNumber): FellowshipDecklist
    {
        $this->deckNumber = $deckNumber;

        return $this;
    }

    /**
     * Get deckNumber.
     */
    public function getDeckNumber(): int
    {
        return $this->deckNumber;
    }

    /**
     * Set fellowship.
     */
    public function setFellowship(Fellowship $fellowship): FellowshipDecklist
    {
        $this->fellowship = $fellowship;

        return $this;
    }

    /**
     * Get fellowship.
     */
    public function getFellowship(): Fellowship
    {
        return $this->fellowship;
    }

    /**
     * Get decklist.
     */
    public function getDecklist(): Decklist
    {
        return $this->decklist;
    }

    /**
     * Set decklist.
     */
    public function setDecklist(Decklist $decklist): FellowshipDecklist
    {
        $this->decklist = $decklist;

        return $this;
    }
}
