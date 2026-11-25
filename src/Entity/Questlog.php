<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Questlog.
 */
#[ORM\Entity(repositoryClass: \App\Repository\QuestlogRepository::class)]
#[ORM\Table(name: 'questlog')]
class Questlog
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'name_canonical', type: 'string', length: 255)]
    private $nameCanonical;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'description_md', type: 'text', nullable: true)]
    private $descriptionMd;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'description_html', type: 'text', nullable: true)]
    private $descriptionHtml;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'date_played', type: 'datetime', nullable: false)]
    private $datePlayed;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'quest_mode', type: 'string', length: 32)]
    private $questMode;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean', nullable: false)]
    private $success = false;

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'smallint', nullable: true)]
    private $score;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nb_decks', type: 'integer')]
    private $nbDecks = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nb_votes', type: 'integer')]
    private $nbVotes = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nb_favorites', type: 'integer')]
    private $nbFavorites = 0;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nb_comments', type: 'integer')]
    private $nbComments = 0;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_public', type: 'boolean', nullable: false)]
    private $isPublic = false;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'date_creation', type: 'datetime', nullable: false)]
    private \DateTime $dateCreation;

    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'date_update', type: 'datetime', nullable: false)]
    private \DateTime $dateUpdate;

    /**
     * @var Collection<int, QuestlogDeck>
     */
    #[ORM\OneToMany(targetEntity: QuestlogDeck::class, mappedBy: 'questlog', cascade: ['persist', 'remove'])]
    private $decks;

    /**
     * @var Collection<int, QuestlogComment>
     */
    #[ORM\OneToMany(targetEntity: QuestlogComment::class, mappedBy: 'questlog', cascade: ['persist', 'remove'])]
    #[ORM\OrderBy(['dateCreation' => \SortDirection::Ascending])]
    private $comments;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'questlogs')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Scenario::class, inversedBy: 'questlogs')]
    #[ORM\JoinColumn(name: 'scenario_id', referencedColumnName: 'id', nullable: false)]
    private ?Scenario $scenario = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'questlog_favorites')]
    #[ORM\JoinTable(name: 'questlog_favorite', joinColumns: [new ORM\JoinColumn(name: 'questlog_id', referencedColumnName: 'id')], inverseJoinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')])]
    private $favorites;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'questlog_votes')]
    #[ORM\JoinTable(name: 'questlog_vote', joinColumns: [new ORM\JoinColumn(name: 'questlog_id', referencedColumnName: 'id')], inverseJoinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')])]
    private $votes;

    /**
     * Constructor.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->decks = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->dateCreation = new \DateTime();
        $this->dateUpdate = new \DateTime();
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name): Questlog
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set nameCanonical.
     *
     * @param string $nameCanonical
     */
    public function setNameCanonical($nameCanonical): Questlog
    {
        $this->nameCanonical = $nameCanonical;

        return $this;
    }

    /**
     * Get nameCanonical.
     */
    public function getNameCanonical(): ?string
    {
        return $this->nameCanonical;
    }

    /**
     * Set descriptionMd.
     *
     * @param string|null $descriptionMd
     */
    public function setDescriptionMd($descriptionMd): Questlog
    {
        $this->descriptionMd = $descriptionMd;

        return $this;
    }

    /**
     * Get descriptionMd.
     */
    public function getDescriptionMd(): ?string
    {
        return $this->descriptionMd;
    }

    /**
     * Set descriptionHtml.
     *
     * @param string|null $descriptionHtml
     */
    public function setDescriptionHtml($descriptionHtml): Questlog
    {
        $this->descriptionHtml = $descriptionHtml;

        return $this;
    }

    /**
     * Get descriptionHtml.
     */
    public function getDescriptionHtml(): ?string
    {
        return $this->descriptionHtml;
    }

    /**
     * Set datePlayed.
     *
     * @param \DateTime $datePlayed
     */
    public function setDatePlayed($datePlayed): Questlog
    {
        $this->datePlayed = $datePlayed;

        return $this;
    }

    /**
     * Get datePlayed.
     */
    public function getDatePlayed(): ?\DateTime
    {
        return $this->datePlayed;
    }

    /**
     * Set questMode.
     *
     * @param string $questMode
     */
    public function setQuestMode($questMode): Questlog
    {
        $this->questMode = $questMode;

        return $this;
    }

    /**
     * Get questMode.
     */
    public function getQuestMode(): ?string
    {
        return $this->questMode;
    }

    /**
     * Set success.
     *
     * @param bool $success
     */
    public function setSuccess($success): Questlog
    {
        $this->success = $success;

        return $this;
    }

    /**
     * Get success.
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Set score.
     *
     * @param int|null $score
     */
    public function setScore($score): Questlog
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score.
     */
    public function getScore(): ?int
    {
        return $this->score;
    }

    /**
     * Set nbDecks.
     *
     * @param int $nbDecks
     */
    public function setNbDecks($nbDecks): Questlog
    {
        $this->nbDecks = $nbDecks;

        return $this;
    }

    /**
     * Get nbDecks.
     */
    public function getNbDecks(): int
    {
        return $this->nbDecks;
    }

    /**
     * Set nbVotes.
     *
     * @param int $nbVotes
     */
    public function setNbVotes($nbVotes): Questlog
    {
        $this->nbVotes = $nbVotes;

        return $this;
    }

    /**
     * Get nbVotes.
     */
    public function getNbVotes(): int
    {
        return $this->nbVotes;
    }

    /**
     * Set nbFavorites.
     *
     * @param int $nbFavorites
     */
    public function setNbFavorites($nbFavorites): Questlog
    {
        $this->nbFavorites = $nbFavorites;

        return $this;
    }

    /**
     * Get nbFavorites.
     */
    public function getNbFavorites(): int
    {
        return $this->nbFavorites;
    }

    /**
     * Set nbComments.
     *
     * @param int $nbComments
     */
    public function setNbComments($nbComments): Questlog
    {
        $this->nbComments = $nbComments;

        return $this;
    }

    /**
     * Get nbComments.
     */
    public function getNbComments(): int
    {
        return $this->nbComments;
    }

    /**
     * Set isPublic.
     *
     * @param bool $isPublic
     */
    public function setIsPublic($isPublic): Questlog
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * Get isPublic.
     */
    public function getIsPublic(): bool
    {
        return $this->isPublic;
    }

    /**
     * Set dateCreation.
     */
    public function setDateCreation(\DateTime $dateCreation): Questlog
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
     * Set dateUpdate.
     */
    public function setDateUpdate(\DateTime $dateUpdate): Questlog
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate.
     */
    public function getDateUpdate(): \DateTime
    {
        return $this->dateUpdate;
    }

    /**
     * Add deck.
     */
    public function addDeck(QuestlogDeck $deck): Questlog
    {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck.
     */
    public function removeDeck(QuestlogDeck $deck): void
    {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks.
     *
     * @return Collection<int, QuestlogDeck>
     */
    public function getDecks(): Collection
    {
        return $this->decks;
    }

    /**
     * Add comment.
     */
    public function addComment(QuestlogComment $comment): Questlog
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     */
    public function removeComment(QuestlogComment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return Collection<int, QuestlogComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * Set user.
     */
    public function setUser(User $user): Questlog
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set scenario.
     */
    public function setScenario(Scenario $scenario): Questlog
    {
        $this->scenario = $scenario;

        return $this;
    }

    /**
     * Get scenario.
     */
    public function getScenario(): ?Scenario
    {
        return $this->scenario;
    }

    /**
     * Add favorite.
     */
    public function addFavorite(User $favorite): Questlog
    {
        $this->favorites[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite.
     */
    public function removeFavorite(User $favorite): void
    {
        $this->favorites->removeElement($favorite);
    }

    /**
     * Get favorites.
     *
     * @return Collection<int, User>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    /**
     * Add vote.
     */
    public function addVote(User $vote): Questlog
    {
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote.
     */
    public function removeVote(User $vote): void
    {
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes.
     *
     * @return Collection<int, User>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'date_publish', type: 'datetime', nullable: true)]
    private $datePublish;

    /**
     * Set datePublish.
     *
     * @param \DateTime|null $datePublish
     */
    public function setDatePublish($datePublish): Questlog
    {
        $this->datePublish = $datePublish;

        return $this;
    }

    /**
     * Get datePublish.
     */
    public function getDatePublish(): ?\DateTime
    {
        return $this->datePublish;
    }
}
