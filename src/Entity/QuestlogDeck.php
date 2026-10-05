<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * QuestlogDeck.
 */
class QuestlogDeck
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var int
     */
    private $deckNumber;

    /**
     * @var string
     */
    private $content;

    private \App\Entity\Questlog $questlog;

    private ?\App\Entity\Deck $deck = null;

    private ?\App\Entity\Decklist $decklist = null;

    public function __construct(Questlog $questlog)
    {
        $this->questlog = $questlog;
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
    public function setDeckNumber($deckNumber): QuestlogDeck
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
     * Set content.
     *
     * @param string $content
     */
    public function setContent($content): QuestlogDeck
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Set questlog.
     */
    public function setQuestlog(Questlog $questlog): QuestlogDeck
    {
        $this->questlog = $questlog;

        return $this;
    }

    /**
     * Get questlog.
     */
    public function getQuestlog(): Questlog
    {
        return $this->questlog;
    }

    /**
     * Set deck.
     */
    public function setDeck(?Deck $deck = null): QuestlogDeck
    {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck.
     */
    public function getDeck(): ?Deck
    {
        return $this->deck;
    }

    /**
     * Set decklist.
     */
    public function setDecklist(?Decklist $decklist = null): QuestlogDeck
    {
        $this->decklist = $decklist;

        return $this;
    }

    /**
     * Get decklist.
     */
    public function getDecklist(): ?Decklist
    {
        return $this->decklist;
    }

    /**
     * @var string|null
     */
    private $player;

    /**
     * Set player.
     *
     * @param string|null $player
     */
    public function setPlayer($player): QuestlogDeck
    {
        $this->player = $player;

        return $this;
    }

    /**
     * Get player.
     */
    public function getPlayer(): ?string
    {
        return $this->player;
    }
}
