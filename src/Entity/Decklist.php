<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\ExportableDeck;
use App\Model\SlotCollectionDecorator;
use App\Model\SlotCollectionInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * @ORM\Entity(repositoryClass="App\Repository\DecklistRepository")
 * @ORM\Table(
 *     name="decklist",
 *     indexes={
 *         @ORM\Index(name="idx_decklist_date_creation", columns={"date_creation"})
 *     }
 * )
 */
class Decklist extends ExportableDeck implements \JsonSerializable
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
     * @var string
     *
     * @ORM\Column(type="string", length=32)
     */
    private $signature;

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
     * @var bool|null
     *
     * @ORM\Column(name="freeze_comments", type="boolean", nullable=true)
     */
    private $freezeComments;

    /**
     * @var string
     *
     * @ORM\Column(type="string", length=8)
     */
    private $version;

    /**
     * @var Collection<int, Decklistslot>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Decklistslot", mappedBy="decklist", cascade={"persist", "remove"})
     */
    private $slots;

    /**
     * @var Collection<int, Decklistsideslot>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Decklistsideslot", mappedBy="decklist", cascade={"persist", "remove"})
     */
    private $sideslots;

    /**
     * @var Collection<int, Comment>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="decklist", cascade={"persist", "remove"})
     * @ORM\OrderBy({"dateCreation"="ASC"})
     */
    private $comments;

    /**
     * @var Collection<int, Decklist>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Decklist", mappedBy="precedent")
     * @ORM\OrderBy({"dateCreation"="ASC"})
     */
    private $successors;

    /**
     * @var Collection<int, Deck>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Deck", mappedBy="parent")
     */
    private $children;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Pack")
     * @ORM\JoinColumn(name="last_pack_id", referencedColumnName="id")
     */
    private ?Pack $lastPack = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Deck", inversedBy="children")
     * @ORM\JoinColumn(name="parent_deck_id", referencedColumnName="id")
     */
    private ?Deck $parent = null;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Decklist", inversedBy="successors")
     * @ORM\JoinColumn(name="precedent_decklist_id", referencedColumnName="id")
     */
    private ?Decklist $precedent = null;

    /**
     * @var Collection<int, User>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="favorites")
     * @ORM\JoinTable(
     *     name="favorite",
     *     joinColumns={@ORM\JoinColumn(name="decklist_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $favorites;

    /**
     * @var Collection<int, User>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="votes")
     * @ORM\JoinTable(
     *     name="vote",
     *     joinColumns={@ORM\JoinColumn(name="decklist_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")}
     * )
     */
    private $votes;

    /**
     * @var Collection<int, QuestlogDeck>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\QuestlogDeck", mappedBy="decklist", cascade={"persist"})
     */
    private $questlogs;

    /**
     * @var int
     *
     * @ORM\Column(name="starting_threat", type="smallint", nullable=false)
     */
    private $startingThreat;

    /**
     * @var Collection<int, FellowshipDecklist>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\FellowshipDecklist", mappedBy="decklist", cascade={"persist", "remove"})
     */
    private $fellowships;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Sphere")
     * @ORM\JoinColumn(name="predominant_sphere_id", referencedColumnName="id")
     */
    private ?Sphere $predominantSphere = null;

    /**
     * @var Collection<int, Sphere>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Sphere")
     * @ORM\JoinTable(
     *     name="decklist_spheres",
     *     joinColumns={@ORM\JoinColumn(name="decklist_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="sphere_id", referencedColumnName="id")}
     * )
     */
    private $spheres;

    /**
     * Constructor.
     */
    public function __construct(
        /**
         * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="decklists")
         * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
         */
        private User $user
    ) {
        $this->slots = new ArrayCollection();
        $this->sideslots = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->successors = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->spheres = new ArrayCollection();
        $this->fellowships = new ArrayCollection();
        $this->questlogs = new ArrayCollection();
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
    public function setName($name): Decklist
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
    public function setNameCanonical($nameCanonical): Decklist
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
     * Set dateCreation.
     */
    public function setDateCreation(\DateTime $dateCreation): Decklist
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
    public function setDateUpdate(\DateTime $dateUpdate): Decklist
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
    public function setDateLastComment($dateLastComment): Decklist
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
     * Set descriptionMd.
     *
     * @param string|null $descriptionMd
     */
    public function setDescriptionMd($descriptionMd): Decklist
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
    public function setDescriptionHtml($descriptionHtml): Decklist
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
     * Set signature.
     *
     * @param string $signature
     */
    public function setSignature($signature): Decklist
    {
        $this->signature = $signature;

        return $this;
    }

    /**
     * Get signature.
     */
    public function getSignature(): string
    {
        return $this->signature;
    }

    /**
     * Set nbVotes.
     *
     * @param int $nbVotes
     */
    public function setNbVotes($nbVotes): Decklist
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
    public function setNbFavorites($nbFavorites): Decklist
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
    public function setNbComments($nbComments): Decklist
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
     * Set freezeComments.
     *
     * @param bool|null $freezeComments
     */
    public function setFreezeComments($freezeComments): Decklist
    {
        $this->freezeComments = $freezeComments;

        return $this;
    }

    /**
     * Get freezeComments.
     */
    public function getFreezeComments(): ?bool
    {
        return $this->freezeComments;
    }

    /**
     * Add slot.
     */
    public function addSlot(Decklistslot $slot): Decklist
    {
        $this->slots[] = $slot;

        return $this;
    }

    /**
     * Remove slot.
     */
    public function removeSlot(Decklistslot $slot): void
    {
        $this->slots->removeElement($slot);
    }

    /**
     * Get slots.
     *
     * @return SlotCollectionInterface<Decklistslot>
     */
    public function getSlots(): SlotCollectionInterface
    {
        return new SlotCollectionDecorator($this->slots);
    }

    /**
     * Add sideslot.
     */
    public function addSideslot(Decklistsideslot $sideslots): Decklist
    {
        $this->sideslots[] = $sideslots;

        return $this;
    }

    /**
     * Remove sideslot.
     */
    public function removeSideslot(Decklistsideslot $sideslots): void
    {
        $this->sideslots->removeElement($sideslots);
    }

    /**
     * Get slots.
     *
     * @return SlotCollectionInterface<Decklistsideslot>
     */
    public function getSideslots(): SlotCollectionInterface
    {
        return new SlotCollectionDecorator($this->sideslots);
    }

    /**
     * Add comment.
     */
    public function addComment(Comment $comment): Decklist
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     */
    public function removeComment(Comment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * Add successor.
     */
    public function addSuccessor(Decklist $successor): Decklist
    {
        $this->successors[] = $successor;

        return $this;
    }

    /**
     * Remove successor.
     */
    public function removeSuccessor(Decklist $successor): void
    {
        $this->successors->removeElement($successor);
    }

    /**
     * Get successors.
     *
     * @return Collection<int, Decklist>
     */
    public function getSuccessors(): Collection
    {
        return $this->successors;
    }

    /**
     * Add child.
     */
    public function addChild(Deck $child): Decklist
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child.
     */
    public function removeChild(Deck $child): void
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children.
     *
     * @return Collection<int, Deck>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Set user.
     */
    public function setUser(User $user): Decklist
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
     * Set lastPack.
     */
    public function setLastPack(?Pack $lastPack = null): Decklist
    {
        $this->lastPack = $lastPack;

        return $this;
    }

    /**
     * Get lastPack.
     */
    public function getLastPack(): ?Pack
    {
        return $this->lastPack;
    }

    /**
     * Set parent.
     */
    public function setParent(?Deck $parent = null): Decklist
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     */
    public function getParent(): ?Deck
    {
        return $this->parent;
    }

    /**
     * Set precedent.
     */
    public function setPrecedent(?Decklist $precedent = null): Decklist
    {
        $this->precedent = $precedent;

        return $this;
    }

    /**
     * Get precedent.
     */
    public function getPrecedent(): ?Decklist
    {
        return $this->precedent;
    }

    /**
     * Add favorite.
     */
    public function addFavorite(User $favorite): Decklist
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
    public function addVote(User $vote): Decklist
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
     * Set version.
     *
     * @param string $version
     */
    public function setVersion($version): Decklist
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Add sphere.
     */
    public function addSphere(Sphere $sphere): Decklist
    {
        if (!$this->spheres->contains($sphere)) {
            $this->spheres[] = $sphere;
        }

        return $this;
    }

    /**
     * Remove sphere.
     */
    public function removeSphere(Sphere $sphere): void
    {
        $this->spheres->removeElement($sphere);
    }

    /**
     * Get spheres.
     *
     * @return Collection<int, Sphere>
     */
    public function getSpheres(): Collection
    {
        return $this->spheres;
    }

    /**
     * Set predominantSphere.
     */
    public function setPredominantSphere(?Sphere $predominantSphere = null): Decklist
    {
        $this->predominantSphere = $predominantSphere;

        return $this;
    }

    /**
     * Get predominantSphere.
     */
    public function getPredominantSphere(): ?Sphere
    {
        return $this->predominantSphere;
    }

    /**
     * Add fellowship.
     */
    public function addFellowship(FellowshipDecklist $fellowship): Decklist
    {
        $this->fellowships[] = $fellowship;

        return $this;
    }

    /**
     * Remove fellowship.
     */
    public function removeFellowship(FellowshipDecklist $fellowship): void
    {
        $this->fellowships->removeElement($fellowship);
    }

    /**
     * Get fellowships.
     *
     * @return Collection<int, FellowshipDecklist>
     */
    public function getFellowships(): Collection
    {
        return $this->fellowships;
    }

    /**
     * Get allFellowships.
     *
     * @return array<int, FellowshipDecklist>
     */
    public function getAllFellowships(): array
    {
        $allFellowships = $this->getFellowships()->toArray();

        return array_filter($allFellowships, fn (FellowshipDecklist $k): bool => $k->getFellowship()->getIsPublic());
    }

    /**
     * Set startingThreat.
     *
     * @param int $startingThreat
     */
    public function setStartingThreat($startingThreat): Decklist
    {
        $this->startingThreat = $startingThreat;

        return $this;
    }

    /**
     * Get startingThreat.
     */
    public function getStartingThreat(): int
    {
        return $this->startingThreat;
    }

    /**
     * Add questlog.
     */
    public function addQuestlog(QuestlogDeck $questlog): Decklist
    {
        $this->questlogs[] = $questlog;

        return $this;
    }

    /**
     * Remove questlog.
     */
    public function removeQuestlog(QuestlogDeck $questlog): void
    {
        $this->questlogs->removeElement($questlog);
    }

    /**
     * Get questlogs.
     *
     * @return Collection<int, QuestlogDeck>
     */
    public function getQuestlogs(): Collection
    {
        return $this->questlogs;
    }

    /**
     * Get allQuestlogs.
     *
     * @return array<int, QuestlogDeck>
     */
    public function getAllQuestlogs(): array
    {
        $theseLogs = $this->getQuestlogs()->toArray();
        $parentLogs = [];
        if ($this->getParent() instanceof Deck) {
            $parentLogs = $this->getParent()->getQuestlogs()->toArray();
        }

        $allQuestlogs = array_unique(array_merge($theseLogs, $parentLogs), SORT_REGULAR);

        return array_filter($allQuestlogs, fn (QuestlogDeck $k): bool => $k->getQuestlog()->getIsPublic());
    }

    /**
     * @return array{
     *     id: int|null,
     *     name: string,
     *     date_creation: string,
     *     date_update: string,
     *     description_md: string|null,
     *     user_id: int|null,
     *     heroes: array<int|string, int>,
     *     slots: array<int|string, int>,
     *     sideslots: array<int|string, int>,
     *     version: string,
     *     last_pack: string,
     *     freeze_comments?: bool|null,
     *     is_published: true,
     *     nb_votes: int,
     *     nb_favorites: int,
     *     nb_comments: int,
     *     starting_threat: int,
     * }
     */
    public function jsonSerialize(): array
    {
        $array = parent::getArrayExport();
        $array['is_published'] = true;
        $array['nb_votes'] = $this->getNbVotes();
        $array['nb_favorites'] = $this->getNbFavorites();
        $array['nb_comments'] = $this->getNbComments();
        $array['starting_threat'] = $this->getStartingThreat();

        return $array;
    }
}
