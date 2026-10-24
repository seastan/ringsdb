<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * QuestlogDeck.
 */
#[ORM\Entity]
#[ORM\Table(name: 'questlog_deck')]
class QuestlogDeck
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
    #[ORM\Column(name: 'deck_number', type: 'smallint', nullable: false)]
    private $deckNumber;

    /**
     * @var string
     */
    #[ORM\Column(type: 'text', nullable: false)]
    private $content;

    #[ORM\ManyToOne(targetEntity: Questlog::class, inversedBy: 'decks')]
    #[ORM\JoinColumn(name: 'questlog_id', referencedColumnName: 'id', nullable: false)]
    private Questlog $questlog;

    #[ORM\ManyToOne(targetEntity: Deck::class, inversedBy: 'questlogs')]
    #[ORM\JoinColumn(name: 'deck_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Deck $deck = null;

    #[ORM\ManyToOne(targetEntity: Decklist::class, inversedBy: 'questlogs')]
    #[ORM\JoinColumn(name: 'decklist_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    private ?Decklist $decklist = null;

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
    #[ORM\Column(type: 'string', length: 80, nullable: true)]
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
