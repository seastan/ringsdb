<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * FellowshipDecklist.
 */
class FellowshipDecklist
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var int
     */
    private $deckNumber;
    /**
     * @var Fellowship
     */
    private $fellowship;
    /**
     * @var Decklist
     */
    private $decklist;

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
     * Set deckNumber.
     *
     * @param int $deckNumber
     *
     * @return FellowshipDecklist
     */
    public function setDeckNumber($deckNumber)
    {
        $this->deckNumber = $deckNumber;

        return $this;
    }

    /**
     * Get deckNumber.
     *
     * @return int
     */
    public function getDeckNumber()
    {
        return $this->deckNumber;
    }

    /**
     * Set fellowship.
     *
     * @return FellowshipDecklist
     */
    public function setFellowship(Fellowship $fellowship)
    {
        $this->fellowship = $fellowship;

        return $this;
    }

    /**
     * Get fellowship.
     *
     * @return Fellowship
     */
    public function getFellowship()
    {
        return $this->fellowship;
    }

    /**
     * Get decklist.
     *
     * @return Decklist
     */
    public function getDecklist()
    {
        return $this->decklist;
    }

    /**
     * Set decklist.
     *
     * @return FellowshipDecklist
     */
    public function setDecklist(Decklist $decklist)
    {
        $this->decklist = $decklist;

        return $this;
    }
}
