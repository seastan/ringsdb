<?php

namespace App\Entity;

/**
 * QuestlogDeck
 */
class QuestlogDeck {
    /**
     * @var integer
     */
    private $id;
    /**
     * @var integer
     */
    private $deckNumber;
    /**
     * @var string
     */
    private $content;
    /**
     * @var \App\Entity\Questlog
     */
    private $questlog;
    /**
     * @var \App\Entity\Deck|null
     */
    private $deck;
    /**
     * @var \App\Entity\Decklist|null
     */
    private $decklist;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set deckNumber
     *
     * @param integer $deckNumber
     *
     * @return QuestlogDeck
     */
    public function setDeckNumber($deckNumber) {
        $this->deckNumber = $deckNumber;

        return $this;
    }

    /**
     * Get deckNumber
     *
     * @return integer
     */
    public function getDeckNumber() {
        return $this->deckNumber;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return QuestlogDeck
     */
    public function setContent($content) {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent() {
        return $this->content;
    }

    /**
     * Set questlog
     *
     * @param \App\Entity\Questlog $questlog
     *
     * @return QuestlogDeck
     */
    public function setQuestlog(\App\Entity\Questlog $questlog) {
        $this->questlog = $questlog;

        return $this;
    }

    /**
     * Get questlog
     *
     * @return \App\Entity\Questlog
     */
    public function getQuestlog() {
        return $this->questlog;
    }

    /**
     * Set deck
     *
     * @param \App\Entity\Deck $deck
     *
     * @return QuestlogDeck
     */
    public function setDeck(\App\Entity\Deck $deck = null) {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck
     *
     * @return \App\Entity\Deck|null
     */
    public function getDeck() {
        return $this->deck;
    }

    /**
     * Set decklist
     *
     * @param \App\Entity\Decklist $decklist
     *
     * @return QuestlogDeck
     */
    public function setDecklist(\App\Entity\Decklist $decklist = null) {
        $this->decklist = $decklist;

        return $this;
    }

    /**
     * Get decklist
     *
     * @return \App\Entity\Decklist|null
     */
    public function getDecklist() {
        return $this->decklist;
    }

    /**
     * @var string|null
     */
    private $player;

    /**
     * Set player
     *
     * @param string|null $player
     *
     * @return QuestlogDeck
     */
    public function setPlayer($player) {
        $this->player = $player;

        return $this;
    }

    /**
     * Get player
     *
     * @return string|null
     */
    public function getPlayer() {
        return $this->player;
    }
}
