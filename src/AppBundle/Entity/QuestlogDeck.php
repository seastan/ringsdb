<?php

namespace AppBundle\Entity;

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
     * @var \AppBundle\Entity\Questlog
     */
    private $questlog;
    /**
     * @var \AppBundle\Entity\Deck
     */
    private $deck;
    /**
     * @var \AppBundle\Entity\Decklist
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
     * @param \AppBundle\Entity\Questlog $questlog
     *
     * @return QuestlogDeck
     */
    public function setQuestlog(\AppBundle\Entity\Questlog $questlog = null) {
        $this->questlog = $questlog;

        return $this;
    }

    /**
     * Get questlog
     *
     * @return \AppBundle\Entity\Questlog
     */
    public function getQuestlog() {
        return $this->questlog;
    }

    /**
     * Set deck
     *
     * @param \AppBundle\Entity\Deck $deck
     *
     * @return QuestlogDeck
     */
    public function setDeck(\AppBundle\Entity\Deck $deck = null) {
        $this->deck = $deck;

        return $this;
    }

    /**
     * Get deck
     *
     * @return \AppBundle\Entity\Deck
     */
    public function getDeck() {
        return $this->deck;
    }

    /**
     * Set decklist
     *
     * @param \AppBundle\Entity\Decklist $decklist
     *
     * @return QuestlogDeck
     */
    public function setDecklist(\AppBundle\Entity\Decklist $decklist = null) {
        $this->decklist = $decklist;

        return $this;
    }

    /**
     * Get decklist
     *
     * @return \AppBundle\Entity\Decklist
     */
    public function getDecklist() {
        return $this->decklist;
    }

    /**
     * @var string
     */
    private $player;

    /**
     * Set player
     *
     * @param string $player
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
     * @return string
     */
    public function getPlayer() {
        return $this->player;
    }
}
