<?php

namespace App\Entity;

/**
 * QuestlogDeck.
 */
class QuestlogDeck
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
     * @var string
     */
    private $content;
    /**
     * @var Questlog
     */
    private $questlog;
    /**
     * @var Deck|null
     */
    private $deck;
    /**
     * @var Decklist|null
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
     * @return QuestlogDeck
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
     * Set content.
     *
     * @param string $content
     *
     * @return QuestlogDeck
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set questlog.
     *
     * @return QuestlogDeck
     */
    public function setQuestlog(Questlog $questlog)
    {
        $this->questlog = $questlog;

        return $this;
    }

    /**
     * Get questlog.
     *
     * @return Questlog
     */
    public function getQuestlog()
    {
        return $this->questlog;
    }

    /**
     * Set deck.
     *
     * @return QuestlogDeck
     */
    public function setDeck(?Deck $deck = null)
    {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck.
     *
     * @return Deck|null
     */
    public function getDeck()
    {
        return $this->deck;
    }

    /**
     * Set decklist.
     *
     * @return QuestlogDeck
     */
    public function setDecklist(?Decklist $decklist = null)
    {
        $this->decklist = $decklist;

        return $this;
    }

    /**
     * Get decklist.
     *
     * @return Decklist|null
     */
    public function getDecklist()
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
     *
     * @return QuestlogDeck
     */
    public function setPlayer($player)
    {
        $this->player = $player;

        return $this;
    }

    /**
     * Get player.
     *
     * @return string|null
     */
    public function getPlayer()
    {
        return $this->player;
    }
}
