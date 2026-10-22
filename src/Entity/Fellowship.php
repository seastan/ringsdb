<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Fellowship.
 *
 * @ORM\Entity(repositoryClass="App\Repository\FellowshipRepository")
 * @ORM\Table(name="fellowship")
 */
class Fellowship
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
     * @var string
     *
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="name_canonical", type="string", length=255)
     */
    private $nameCanonical;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description_md", type="text", nullable=true)
     */
    private $descriptionMd;

    /**
     * @var string|null
     *
     * @ORM\Column(name="description_html", type="text", nullable=true)
     */
    private $descriptionHtml;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_public", type="boolean", nullable=false)
     */
    private $isPublic = false;

    /**
     * @var int
     *
     * @ORM\Column(name="nb_votes", type="integer")
     */
    private $nbVotes = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="nb_favorites", type="integer")
     */
    private $nbFavorites = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="nb_comments", type="integer")
     */
    private $nbComments = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="nb_decks", type="integer")
     */
    private $nbDecks = 0;

    /**
     * @ORM\Column(name="date_creation", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     */
    private \DateTime $dateCreation;

    /**
     * @ORM\Column(name="date_update", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="update")
     */
    private \DateTime $dateUpdate;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_last_comment", type="datetime", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    private $dateLastComment;

    /**
     * @var Collection<int, FellowshipDeck>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\FellowshipDeck", mappedBy="fellowship", cascade={"persist", "remove"})
     */
    private $decks;

    /**
     * @var Collection<int, FellowshipDecklist>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\FellowshipDecklist", mappedBy="fellowship", cascade={"persist", "remove"})
     */
    private $decklists;

    /**
     * @var Collection<int, FellowshipComment>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\FellowshipComment", mappedBy="fellowship", cascade={"persist", "remove"})
     * @ORM\OrderBy({"dateCreation"="ASC"})
     */
    private $comments;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="fellowships")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private User $user;

    /**
     * @var Collection<int, User>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="fellowship_favorites")
     * @ORM\JoinTable(
     *     name="fellowship_favorite",
     *     joinColumns={@ORM\JoinColumn(name="fellowship_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $favorites;

    /**
     * @var Collection<int, User>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="fellowship_votes")
     * @ORM\JoinTable(
     *     name="fellowship_vote",
     *     joinColumns={@ORM\JoinColumn(name="fellowship_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $votes;

    /**
     * Constructor.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->decks = new ArrayCollection();
        $this->decklists = new ArrayCollection();
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
    public function setName($name): Fellowship
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set nameCanonical.
     *
     * @param string $nameCanonical
     */
    public function setNameCanonical($nameCanonical): Fellowship
    {
        $this->nameCanonical = $nameCanonical;

        return $this;
    }

    /**
     * Get nameCanonical.
     */
    public function getNameCanonical(): string
    {
        return $this->nameCanonical;
    }

    /**
     * Set descriptionMd.
     *
     * @param string|null $descriptionMd
     */
    public function setDescriptionMd($descriptionMd): Fellowship
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
    public function setDescriptionHtml($descriptionHtml): Fellowship
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
     * Set isPublic.
     *
     * @param bool $isPublic
     */
    public function setIsPublic($isPublic): Fellowship
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
     * Set nbVotes.
     *
     * @param int $nbVotes
     */
    public function setNbVotes($nbVotes): Fellowship
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
    public function setNbFavorites($nbFavorites): Fellowship
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
    public function setNbComments($nbComments): Fellowship
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
     * Set dateCreation.
     */
    public function setDateCreation(\DateTime $dateCreation): Fellowship
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
    public function setDateUpdate(\DateTime $dateUpdate): Fellowship
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
     * Set dateLastComment.
     *
     * @param \DateTime|null $dateLastComment
     */
    public function setDateLastComment($dateLastComment): Fellowship
    {
        $this->dateLastComment = $dateLastComment;

        return $this;
    }

    /**
     * Get dateLastComment.
     */
    public function getDateLastComment(): ?\DateTime
    {
        return $this->dateLastComment;
    }

    /**
     * Add deck.
     */
    public function addDeck(FellowshipDeck $deck): Fellowship
    {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck.
     */
    public function removeDeck(FellowshipDeck $deck): void
    {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks.
     *
     * @return Collection<int, FellowshipDeck>
     */
    public function getDecks(): Collection
    {
        return $this->decks;
    }

    /**
     * Add decklist.
     */
    public function addDecklist(FellowshipDecklist $decklist): Fellowship
    {
        $this->decklists[] = $decklist;

        return $this;
    }

    /**
     * Remove decklist.
     */
    public function removeDecklist(FellowshipDecklist $decklist): void
    {
        $this->decklists->removeElement($decklist);
    }

    /**
     * Get decklists.
     *
     * @return Collection<int, FellowshipDecklist>
     */
    public function getDecklists(): Collection
    {
        return $this->decklists;
    }

    /**
     * Add comment.
     */
    public function addComment(FellowshipComment $comment): Fellowship
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     */
    public function removeComment(FellowshipComment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return Collection<int, FellowshipComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * Set user.
     */
    public function setUser(User $user): Fellowship
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
     * Add favorite.
     */
    public function addFavorite(User $favorite): Fellowship
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
    public function addVote(User $vote): Fellowship
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
     * Set nbDecks.
     *
     * @param int $nbDecks
     */
    public function setNbDecks($nbDecks): Fellowship
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
     * @var \DateTime|null
     *
     * @ORM\Column(name="date_publish", type="datetime", nullable=true)
     */
    private $datePublish;

    /**
     * Set datePublish.
     *
     * @param \DateTime|null $datePublish
     */
    public function setDatePublish($datePublish): Fellowship
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
