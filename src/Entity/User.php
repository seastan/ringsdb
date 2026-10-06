<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Model\User as BaseUser;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * User.
 *
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="user")
 */
class User extends BaseUser
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function getMaxNbDecks(): float
    {
        return 5 * (100 + floor($this->reputation / 10));
    }

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     */
    private $dateCreation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_update", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="update")
     */
    private $dateUpdate;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private int $reputation;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $resume;

    /**
     * @var string|null
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $color;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private int $donation;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_notif_author", type="boolean", nullable=false, options={"default": true})
     */
    private $isNotifAuthor = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_notif_commenter", type="boolean", nullable=false, options={"default": true})
     */
    private $isNotifCommenter = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_notif_mention", type="boolean", nullable=false, options={"default": true})
     */
    private $isNotifMention = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_notif_follow", type="boolean", nullable=false, options={"default": true})
     */
    private $isNotifFollow = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_notif_successor", type="boolean", nullable=false, options={"default": true})
     */
    private $isNotifSuccessor = true;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_share_decks", type="boolean", nullable=false, options={"default": false})
     */
    private $isShareDecks = false;

    /**
     * @var bool
     *
     * @ORM\Column(name="dark_mode", type="boolean", nullable=false, options={"default": false})
     */
    private $darkMode = false;

    /**
     * @var Collection<int, Deck>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Deck", mappedBy="user", cascade={"remove"})
     * @ORM\OrderBy({"dateUpdate"="DESC"})
     */
    private $decks;

    /**
     * @var Collection<int, Decklist>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Decklist", mappedBy="user")
     */
    private $decklists;

    /**
     * @var Collection<int, Comment>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Comment", mappedBy="user")
     * @ORM\OrderBy({"dateCreation"="DESC"})
     */
    private $comments;

    /**
     * @var Collection<int, Review>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Review", mappedBy="user")
     * @ORM\OrderBy({"dateCreation"="DESC"})
     */
    private $reviews;

    /**
     * @var Collection<int, Decklist>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Decklist", mappedBy="favorites", cascade={"remove"})
     */
    private $favorites;

    /**
     * @var Collection<int, Decklist>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Decklist", mappedBy="votes", cascade={"remove"})
     */
    private $votes;

    /**
     * @var Collection<int, Review>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Review", mappedBy="votes", cascade={"remove"})
     */
    private $reviewvotes;

    public function __construct()
    {
        parent::__construct();

        $this->reputation = 1;
        $this->donation = 0;
        $this->decks = new ArrayCollection();
        $this->decklists = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->reviewvotes = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->fellowships = new ArrayCollection();
        $this->fellowship_comments = new ArrayCollection();
        $this->fellowship_favorites = new ArrayCollection();
        $this->fellowship_votes = new ArrayCollection();
        $this->questlogs = new ArrayCollection();
        $this->questlog_comments = new ArrayCollection();
        $this->questlog_favorites = new ArrayCollection();
        $this->questlog_votes = new ArrayCollection();
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): User
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
     *
     * @param \DateTime $dateUpdate
     */
    public function setDateUpdate($dateUpdate): User
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
     * Set reputation.
     */
    public function setReputation(int $reputation): User
    {
        $this->reputation = $reputation;

        return $this;
    }

    /**
     * Get reputation.
     */
    public function getReputation(): int
    {
        return $this->reputation;
    }

    /**
     * Set resume.
     *
     * @param string|null $resume
     */
    public function setResume($resume): User
    {
        $this->resume = $resume;

        return $this;
    }

    /**
     * Get resume.
     */
    public function getResume(): ?string
    {
        return $this->resume;
    }

    /**
     * Set color.
     *
     * @param string|null $color
     */
    public function setColor($color): User
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color.
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * Set donation.
     */
    public function setDonation(int $donation): User
    {
        $this->donation = $donation;

        return $this;
    }

    /**
     * Get donation.
     */
    public function getDonation(): int
    {
        return $this->donation;
    }

    /**
     * Set isNotifAuthor.
     *
     * @param bool $isNotifAuthor
     */
    public function setIsNotifAuthor($isNotifAuthor): User
    {
        $this->isNotifAuthor = $isNotifAuthor;

        return $this;
    }

    /**
     * Get isNotifAuthor.
     */
    public function getIsNotifAuthor(): bool
    {
        return $this->isNotifAuthor;
    }

    /**
     * Set isNotifCommenter.
     *
     * @param bool $isNotifCommenter
     */
    public function setIsNotifCommenter($isNotifCommenter): User
    {
        $this->isNotifCommenter = $isNotifCommenter;

        return $this;
    }

    /**
     * Get isNotifCommenter.
     */
    public function getIsNotifCommenter(): bool
    {
        return $this->isNotifCommenter;
    }

    /**
     * Set isNotifMention.
     *
     * @param bool $isNotifMention
     */
    public function setIsNotifMention($isNotifMention): User
    {
        $this->isNotifMention = $isNotifMention;

        return $this;
    }

    /**
     * Get isNotifMention.
     */
    public function getIsNotifMention(): bool
    {
        return $this->isNotifMention;
    }

    /**
     * Set isNotifFollow.
     *
     * @param bool $isNotifFollow
     */
    public function setIsNotifFollow($isNotifFollow): User
    {
        $this->isNotifFollow = $isNotifFollow;

        return $this;
    }

    /**
     * Get isNotifFollow.
     */
    public function getIsNotifFollow(): bool
    {
        return $this->isNotifFollow;
    }

    /**
     * Set isNotifSuccessor.
     *
     * @param bool $isNotifSuccessor
     */
    public function setIsNotifSuccessor($isNotifSuccessor): User
    {
        $this->isNotifSuccessor = $isNotifSuccessor;

        return $this;
    }

    /**
     * Get isNotifSuccessor.
     */
    public function getIsNotifSuccessor(): bool
    {
        return $this->isNotifSuccessor;
    }

    /**
     * Set isShareDecks.
     *
     * @param bool $isShareDecks
     */
    public function setIsShareDecks($isShareDecks): User
    {
        $this->isShareDecks = $isShareDecks;

        return $this;
    }

    /**
     * Get isShareDecks.
     */
    public function getIsShareDecks(): bool
    {
        return $this->isShareDecks;
    }

    /**
     * Set darkMode.
     *
     * @param bool $darkMode
     */
    public function setDarkMode($darkMode): User
    {
        $this->darkMode = $darkMode;

        return $this;
    }

    /**
     * Get darkMode.
     */
    public function getDarkMode(): bool
    {
        return $this->darkMode;
    }

    /**
     * Add deck.
     */
    public function addDeck(Deck $deck): User
    {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck.
     */
    public function removeDeck(Deck $deck): void
    {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks.
     *
     * @return Collection<int, Deck>
     */
    public function getDecks(): Collection
    {
        return $this->decks;
    }

    /**
     * Add decklist.
     */
    public function addDecklist(Decklist $decklist): User
    {
        $this->decklists[] = $decklist;

        return $this;
    }

    /**
     * Remove decklist.
     */
    public function removeDecklist(Decklist $decklist): void
    {
        $this->decklists->removeElement($decklist);
    }

    /**
     * Get decklists.
     *
     * @return Collection<int, Decklist>
     */
    public function getDecklists(): Collection
    {
        return $this->decklists;
    }

    /**
     * Add comment.
     */
    public function addComment(Comment $comment): User
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
     * Add review.
     */
    public function addReview(Review $review): User
    {
        $this->reviews[] = $review;

        return $this;
    }

    /**
     * Remove review.
     */
    public function removeReview(Review $review): void
    {
        $this->reviews->removeElement($review);
    }

    /**
     * Get reviews.
     *
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    /**
     * Add favorite.
     */
    public function addFavorite(Decklist $favorite): User
    {
        $favorite->addFavorite($this);
        $this->favorites[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite.
     */
    public function removeFavorite(Decklist $favorite): void
    {
        $favorite->removeFavorite($this);
        $this->favorites->removeElement($favorite);
    }

    /**
     * Get favorites.
     *
     * @return Collection<int, Decklist>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    /**
     * Add vote.
     */
    public function addVote(Decklist $vote): User
    {
        $vote->addVote($this);
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote.
     */
    public function removeVote(Decklist $vote): void
    {
        $vote->removeVote($this);
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes.
     *
     * @return Collection<int, Decklist>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    /**
     * Add reviewvote.
     */
    public function addReviewvote(Review $reviewvote): User
    {
        $this->reviewvotes[] = $reviewvote;

        return $this;
    }

    /**
     * Remove reviewvote.
     */
    public function removeReviewvote(Review $reviewvote): void
    {
        $this->reviewvotes->removeElement($reviewvote);
    }

    /**
     * Get reviewvotes.
     *
     * @return Collection<int, Review>
     */
    public function getReviewvotes(): Collection
    {
        return $this->reviewvotes;
    }

    /**
     * @var Collection<int, User>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", mappedBy="followers")
     */
    private $following;

    /**
     * @var Collection<int, User>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\User", inversedBy="following")
     * @ORM\JoinTable(
     *     name="follow",
     *     joinColumns={@ORM\JoinColumn(name="following_id", referencedColumnName="id")},
     *     inverseJoinColumns={@ORM\JoinColumn(name="follower_id", referencedColumnName="id")}
     * )
     */
    private $followers;

    /**
     * Add following.
     */
    public function addFollowing(User $following): User
    {
        $this->following[] = $following;

        return $this;
    }

    /**
     * Remove following.
     */
    public function removeFollowing(User $following): void
    {
        $this->following->removeElement($following);
    }

    /**
     * Get following.
     *
     * @return Collection<int, User>
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    /**
     * Add follower.
     */
    public function addFollower(User $follower): User
    {
        $this->followers[] = $follower;

        return $this;
    }

    /**
     * Remove follower.
     */
    public function removeFollower(User $follower): void
    {
        $this->followers->removeElement($follower);
    }

    /**
     * Get followers.
     *
     * @return Collection<int, User>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $ownedPacks;

    /**
     * Set ownedPacks.
     *
     * @param string|null $ownedPacks
     */
    public function setOwnedPacks($ownedPacks): User
    {
        $this->ownedPacks = $ownedPacks;

        return $this;
    }

    /**
     * Get ownedPacks.
     */
    public function getOwnedPacks(): ?string
    {
        return $this->ownedPacks;
    }

    /**
     * @var string|null
     *
     * @ORM\Column(name="art_preferences", type="text", nullable=true)
     */
    private $artPreferences;

    /**
     * Set artPreferences (JSON map of card code => preferred pack code).
     *
     * @param string|null $artPreferences
     */
    public function setArtPreferences($artPreferences): User
    {
        $this->artPreferences = $artPreferences;

        return $this;
    }

    /**
     * Get artPreferences.
     */
    public function getArtPreferences(): ?string
    {
        return $this->artPreferences;
    }

    /**
     * @var Collection<int, Fellowship>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Fellowship", mappedBy="user", cascade={"remove"})
     * @ORM\OrderBy({"dateUpdate"="DESC"})
     */
    private $fellowships;

    /**
     * Add fellowship.
     */
    public function addFellowship(Fellowship $fellowship): User
    {
        $this->fellowships[] = $fellowship;

        return $this;
    }

    /**
     * Remove fellowship.
     */
    public function removeFellowship(Fellowship $fellowship): void
    {
        $this->fellowships->removeElement($fellowship);
    }

    /**
     * Get fellowships.
     *
     * @return Collection<int, Fellowship>
     */
    public function getFellowships(): Collection
    {
        return $this->fellowships;
    }

    /**
     * @return ArrayCollection<int, mixed>
     */
    public function getPublicFellowships(): ArrayCollection
    {
        $publicFellowships = [];

        foreach ($this->fellowships as $fellowship) {
            /* @var $fellowship \App\Entity\Fellowship */
            if ($fellowship->getIsPublic()) {
                $publicFellowships[] = $fellowship;
            }
        }

        return new ArrayCollection($publicFellowships);
    }

    /**
     * @var Collection<int, FellowshipComment>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\FellowshipComment", mappedBy="user")
     * @ORM\OrderBy({"dateCreation"="DESC"})
     */
    private $fellowship_comments;

    /**
     * @var Collection<int, Fellowship>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Fellowship", mappedBy="favorites", cascade={"remove"})
     */
    private $fellowship_favorites;

    /**
     * @var Collection<int, Fellowship>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Fellowship", mappedBy="votes", cascade={"remove"})
     */
    private $fellowship_votes;

    /**
     * Add fellowshipComment.
     */
    public function addFellowshipComment(FellowshipComment $fellowshipComment): User
    {
        $this->fellowship_comments[] = $fellowshipComment;

        return $this;
    }

    /**
     * Remove fellowshipComment.
     */
    public function removeFellowshipComment(FellowshipComment $fellowshipComment): void
    {
        $this->fellowship_comments->removeElement($fellowshipComment);
    }

    /**
     * Get fellowshipComments.
     *
     * @return Collection<int, FellowshipComment>
     */
    public function getFellowshipComments(): Collection
    {
        return $this->fellowship_comments;
    }

    /**
     * Add fellowshipFavorite.
     */
    public function addFellowshipFavorite(Fellowship $fellowshipFavorite): User
    {
        $this->fellowship_favorites[] = $fellowshipFavorite;

        return $this;
    }

    /**
     * Remove fellowshipFavorite.
     */
    public function removeFellowshipFavorite(Fellowship $fellowshipFavorite): void
    {
        $this->fellowship_favorites->removeElement($fellowshipFavorite);
    }

    /**
     * Get fellowshipFavorites.
     *
     * @return Collection<int, Fellowship>
     */
    public function getFellowshipFavorites(): Collection
    {
        return $this->fellowship_favorites;
    }

    /**
     * Add fellowshipVote.
     */
    public function addFellowshipVote(Fellowship $fellowshipVote): User
    {
        $this->fellowship_votes[] = $fellowshipVote;

        return $this;
    }

    /**
     * Remove fellowshipVote.
     */
    public function removeFellowshipVote(Fellowship $fellowshipVote): void
    {
        $this->fellowship_votes->removeElement($fellowshipVote);
    }

    /**
     * Get fellowshipVotes.
     *
     * @return Collection<int, Fellowship>
     */
    public function getFellowshipVotes(): Collection
    {
        return $this->fellowship_votes;
    }

    /**
     * @var Collection<int, Questlog>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\Questlog", mappedBy="user", cascade={"remove"})
     * @ORM\OrderBy({"dateUpdate"="DESC"})
     */
    private $questlogs;

    /**
     * @var Collection<int, QuestlogComment>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\QuestlogComment", mappedBy="user")
     * @ORM\OrderBy({"dateCreation"="DESC"})
     */
    private $questlog_comments;

    /**
     * Add questlog.
     */
    public function addQuestlog(Questlog $questlog): User
    {
        $this->questlogs[] = $questlog;

        return $this;
    }

    /**
     * Remove questlog.
     */
    public function removeQuestlog(Questlog $questlog): void
    {
        $this->questlogs->removeElement($questlog);
    }

    /**
     * Get questlogs.
     *
     * @return Collection<int, Questlog>
     */
    public function getQuestlogs(): Collection
    {
        return $this->questlogs;
    }

    /**
     * Add questlogComment.
     */
    public function addQuestlogComment(QuestlogComment $questlogComment): User
    {
        $this->questlog_comments[] = $questlogComment;

        return $this;
    }

    /**
     * Remove questlogComment.
     */
    public function removeQuestlogComment(QuestlogComment $questlogComment): void
    {
        $this->questlog_comments->removeElement($questlogComment);
    }

    /**
     * Get questlogComments.
     *
     * @return Collection<int, QuestlogComment>
     */
    public function getQuestlogComments(): Collection
    {
        return $this->questlog_comments;
    }

    /**
     * @var Collection<int, Questlog>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Questlog", mappedBy="favorites", cascade={"remove"})
     */
    private $questlog_favorites;

    /**
     * @var Collection<int, Questlog>
     *
     * @ORM\ManyToMany(targetEntity="App\Entity\Questlog", mappedBy="votes", cascade={"remove"})
     */
    private $questlog_votes;

    /**
     * Add questlogFavorite.
     */
    public function addQuestlogFavorite(Questlog $questlogFavorite): User
    {
        $this->questlog_favorites[] = $questlogFavorite;

        return $this;
    }

    /**
     * Remove questlogFavorite.
     */
    public function removeQuestlogFavorite(Questlog $questlogFavorite): void
    {
        $this->questlog_favorites->removeElement($questlogFavorite);
    }

    /**
     * Get questlogFavorites.
     *
     * @return Collection<int, Questlog>
     */
    public function getQuestlogFavorites(): Collection
    {
        return $this->questlog_favorites;
    }

    /**
     * Add questlogVote.
     */
    public function addQuestlogVote(Questlog $questlogVote): User
    {
        $this->questlog_votes[] = $questlogVote;

        return $this;
    }

    /**
     * Remove questlogVote.
     */
    public function removeQuestlogVote(Questlog $questlogVote): void
    {
        $this->questlog_votes->removeElement($questlogVote);
    }

    /**
     * Get questlogVotes.
     *
     * @return Collection<int, Questlog>
     */
    public function getQuestlogVotes(): Collection
    {
        return $this->questlog_votes;
    }

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $locked = false;

    /**
     * Set locked.
     *
     * @param bool $locked
     */
    public function setLocked($locked): User
    {
        $this->locked = (bool) $locked;

        return $this;
    }

    /**
     * Get locked.
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @var bool
     *
     * @ORM\Column(type="boolean")
     */
    protected $expired = false;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="expires_at", type="datetime", nullable=true)
     */
    protected $expiresAt;

    /**
     * @var bool
     *
     * @ORM\Column(name="credentials_expired", type="boolean")
     */
    protected $credentialsExpired = false;

    /**
     * @var \DateTime|null
     *
     * @ORM\Column(name="credentials_expire_at", type="datetime", nullable=true)
     */
    protected $credentialsExpireAt;

    /*
     * FOSUserBundle 2.x hardcodes the three checks below to true, so the
     * locked/expired columns above would otherwise be ignored at login.
     * These restore the 1.x behavior the Symfony UserChecker relies on.
     */

    public function isAccountNonLocked(): bool {
        return !$this->locked;
    }

    public function isAccountNonExpired(): bool {
        if (true === $this->expired) {
            return false;
        }

        if (null !== $this->expiresAt && $this->expiresAt->getTimestamp() < time()) {
            return false;
        }

        return true;
    }

    public function isCredentialsNonExpired(): bool {
        if (true === $this->credentialsExpired) {
            return false;
        }

        if (null !== $this->credentialsExpireAt && $this->credentialsExpireAt->getTimestamp() < time()) {
            return false;
        }

        return true;
    }
}
