<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * User.
 */
class User extends BaseUser
{
    public function getMaxNbDecks(): float
    {
        return 5 * (100 + floor($this->reputation / 10));
    }

    /**
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    /**
     * @var int
     */
    private $reputation;
    /**
     * @var string|null
     */
    private $resume;
    /**
     * @var string|null
     */
    private $color;
    /**
     * @var int
     */
    private $donation;
    /**
     * @var bool
     */
    private $isNotifAuthor = true;
    /**
     * @var bool
     */
    private $isNotifCommenter = true;
    /**
     * @var bool
     */
    private $isNotifMention = true;
    /**
     * @var bool
     */
    private $isNotifFollow = true;
    /**
     * @var bool
     */
    private $isNotifSuccessor = true;
    /**
     * @var bool
     */
    private $isShareDecks = false;
    /**
     * @var bool
     */
    private $darkMode = false;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Deck>
     */
    private $decks;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Decklist>
     */
    private $decklists;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Comment>
     */
    private $comments;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Review>
     */
    private $reviews;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Decklist>
     */
    private $favorites;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Decklist>
     */
    private $votes;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Review>
     */
    private $reviewvotes;

    public function __construct()
    {
        parent::__construct();

        $this->reputation = 1;
        $this->donation = 0;
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
     *
     * @param int $reputation
     */
    public function setReputation($reputation): User
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
     *
     * @param int $donation
     */
    public function setDonation($donation): User
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
     * @return \Doctrine\Common\Collections\Collection<int, Deck>
     */
    public function getDecks(): \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection<int, Decklist>
     */
    public function getDecklists(): \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection<int, Comment>
     */
    public function getComments(): \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection<int, Review>
     */
    public function getReviews(): \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection<int, Decklist>
     */
    public function getFavorites(): \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection<int, Decklist>
     */
    public function getVotes(): \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection<int, Review>
     */
    public function getReviewvotes(): \Doctrine\Common\Collections\Collection
    {
        return $this->reviewvotes;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, User>
     */
    private $following;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, User>
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
     * @return \Doctrine\Common\Collections\Collection<int, User>
     */
    public function getFollowing(): \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection<int, User>
     */
    public function getFollowers(): \Doctrine\Common\Collections\Collection
    {
        return $this->followers;
    }
    /**
     * @var string|null
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
     * @var \Doctrine\Common\Collections\Collection<int, Fellowship>
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
     * @return \Doctrine\Common\Collections\Collection<int, Fellowship>
     */
    public function getFellowships(): \Doctrine\Common\Collections\Collection
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
     * @var \Doctrine\Common\Collections\Collection<int, FellowshipComment>
     */
    private $fellowship_comments;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Fellowship>
     */
    private $fellowship_favorites;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Fellowship>
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
     * @return \Doctrine\Common\Collections\Collection<int, FellowshipComment>
     */
    public function getFellowshipComments(): \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection<int, Fellowship>
     */
    public function getFellowshipFavorites(): \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection<int, Fellowship>
     */
    public function getFellowshipVotes(): \Doctrine\Common\Collections\Collection
    {
        return $this->fellowship_votes;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, Questlog>
     */
    private $questlogs;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, QuestlogComment>
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
     * @return \Doctrine\Common\Collections\Collection<int, Questlog>
     */
    public function getQuestlogs(): \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection<int, QuestlogComment>
     */
    public function getQuestlogComments(): \Doctrine\Common\Collections\Collection
    {
        return $this->questlog_comments;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, Questlog>
     */
    private $questlog_favorites;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Questlog>
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
     * @return \Doctrine\Common\Collections\Collection<int, Questlog>
     */
    public function getQuestlogFavorites(): \Doctrine\Common\Collections\Collection
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
     * @return \Doctrine\Common\Collections\Collection<int, Questlog>
     */
    public function getQuestlogVotes(): \Doctrine\Common\Collections\Collection
    {
        return $this->questlog_votes;
    }

    /**
     * @var bool
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
     */
    protected $expired = false;

    /**
     * @var \DateTime|null
     */
    protected $expiresAt;

    /**
     * @var bool
     */
    protected $credentialsExpired = false;

    /**
     * @var \DateTime|null
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
