<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * User
 */
class User extends BaseUser {
    /**
     * @return float
     */
    public function getMaxNbDecks() {
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
     * @var integer
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
     * @var integer
     */
    private $donation;
    /**
     * @var boolean
     */
    private $isNotifAuthor = true;
    /**
     * @var boolean
     */
    private $isNotifCommenter = true;
    /**
     * @var boolean
     */
    private $isNotifMention = true;
    /**
     * @var boolean
     */
    private $isNotifFollow = true;
    /**
     * @var boolean
     */
    private $isNotifSuccessor = true;
    /**
     * @var boolean
     */
    private $isShareDecks = false;
    /**
     * @var boolean
     */
    private $darkMode = false;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Deck>
     */
    private $decks;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Decklist>
     */
    private $decklists;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Comment>
     */
    private $comments;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Review>
     */
    private $reviews;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Decklist>
     */
    private $favorites;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Decklist>
     */
    private $votes;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Review>
     */
    private $reviewvotes;

    public function __construct() {
        parent::__construct();

        $this->reputation = 1;
        $this->donation = 0;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return User
     */
    public function setDateCreation($dateCreation) {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation
     *
     * @return \DateTime
     */
    public function getDateCreation() {
        return $this->dateCreation;
    }

    /**
     * Set dateUpdate
     *
     * @param \DateTime $dateUpdate
     *
     * @return User
     */
    public function setDateUpdate($dateUpdate) {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate
     *
     * @return \DateTime
     */
    public function getDateUpdate() {
        return $this->dateUpdate;
    }

    /**
     * Set reputation
     *
     * @param integer $reputation
     *
     * @return User
     */
    public function setReputation($reputation) {
        $this->reputation = $reputation;

        return $this;
    }

    /**
     * Get reputation
     *
     * @return integer
     */
    public function getReputation() {
        return $this->reputation;
    }

    /**
     * Set resume
     *
     * @param string|null $resume
     *
     * @return User
     */
    public function setResume($resume) {
        $this->resume = $resume;

        return $this;
    }

    /**
     * Get resume
     *
     * @return string|null
     */
    public function getResume() {
        return $this->resume;
    }

    /**
     * Set color
     *
     * @param string|null $color
     *
     * @return User
     */
    public function setColor($color) {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color
     *
     * @return string|null
     */
    public function getColor() {
        return $this->color;
    }

    /**
     * Set donation
     *
     * @param integer $donation
     *
     * @return User
     */
    public function setDonation($donation) {
        $this->donation = $donation;

        return $this;
    }

    /**
     * Get donation
     *
     * @return integer
     */
    public function getDonation() {
        return $this->donation;
    }

    /**
     * Set isNotifAuthor
     *
     * @param boolean $isNotifAuthor
     *
     * @return User
     */
    public function setIsNotifAuthor($isNotifAuthor) {
        $this->isNotifAuthor = $isNotifAuthor;

        return $this;
    }

    /**
     * Get isNotifAuthor
     *
     * @return boolean
     */
    public function getIsNotifAuthor() {
        return $this->isNotifAuthor;
    }

    /**
     * Set isNotifCommenter
     *
     * @param boolean $isNotifCommenter
     *
     * @return User
     */
    public function setIsNotifCommenter($isNotifCommenter) {
        $this->isNotifCommenter = $isNotifCommenter;

        return $this;
    }

    /**
     * Get isNotifCommenter
     *
     * @return boolean
     */
    public function getIsNotifCommenter() {
        return $this->isNotifCommenter;
    }

    /**
     * Set isNotifMention
     *
     * @param boolean $isNotifMention
     *
     * @return User
     */
    public function setIsNotifMention($isNotifMention) {
        $this->isNotifMention = $isNotifMention;

        return $this;
    }

    /**
     * Get isNotifMention
     *
     * @return boolean
     */
    public function getIsNotifMention() {
        return $this->isNotifMention;
    }

    /**
     * Set isNotifFollow
     *
     * @param boolean $isNotifFollow
     *
     * @return User
     */
    public function setIsNotifFollow($isNotifFollow) {
        $this->isNotifFollow = $isNotifFollow;

        return $this;
    }

    /**
     * Get isNotifFollow
     *
     * @return boolean
     */
    public function getIsNotifFollow() {
        return $this->isNotifFollow;
    }

    /**
     * Set isNotifSuccessor
     *
     * @param boolean $isNotifSuccessor
     *
     * @return User
     */
    public function setIsNotifSuccessor($isNotifSuccessor) {
        $this->isNotifSuccessor = $isNotifSuccessor;

        return $this;
    }

    /**
     * Get isNotifSuccessor
     *
     * @return boolean
     */
    public function getIsNotifSuccessor() {
        return $this->isNotifSuccessor;
    }

    /**
     * Set isShareDecks
     *
     * @param boolean $isShareDecks
     *
     * @return User
     */
    public function setIsShareDecks($isShareDecks) {
        $this->isShareDecks = $isShareDecks;

        return $this;
    }

    /**
     * Get isShareDecks
     *
     * @return boolean
     */
    public function getIsShareDecks() {
        return $this->isShareDecks;
    }

    /**
     * Set darkMode
     *
     * @param boolean $darkMode
     *
     * @return User
     */
    public function setDarkMode($darkMode) {
        $this->darkMode = $darkMode;

        return $this;
    }

    /**
     * Get darkMode
     *
     * @return boolean
     */
    public function getDarkMode() {
        return $this->darkMode;
    }

    /**
     * Add deck
     *
     * @param \App\Entity\Deck $deck
     *
     * @return User
     */
    public function addDeck(\App\Entity\Deck $deck) {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck
     *
     * @param \App\Entity\Deck $deck
     * @return void
     */
    public function removeDeck(\App\Entity\Deck $deck) {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Deck>
     */
    public function getDecks() {
        return $this->decks;
    }

    /**
     * Add decklist
     *
     * @param \App\Entity\Decklist $decklist
     *
     * @return User
     */
    public function addDecklist(\App\Entity\Decklist $decklist) {
        $this->decklists[] = $decklist;

        return $this;
    }

    /**
     * Remove decklist
     *
     * @param \App\Entity\Decklist $decklist
     * @return void
     */
    public function removeDecklist(\App\Entity\Decklist $decklist) {
        $this->decklists->removeElement($decklist);
    }

    /**
     * Get decklists
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Decklist>
     */
    public function getDecklists() {
        return $this->decklists;
    }

    /**
     * Add comment
     *
     * @param \App\Entity\Comment $comment
     *
     * @return User
     */
    public function addComment(\App\Entity\Comment $comment) {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment
     *
     * @param \App\Entity\Comment $comment
     * @return void
     */
    public function removeComment(\App\Entity\Comment $comment) {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Comment>
     */
    public function getComments() {
        return $this->comments;
    }

    /**
     * Add review
     *
     * @param \App\Entity\Review $review
     *
     * @return User
     */
    public function addReview(\App\Entity\Review $review) {
        $this->reviews[] = $review;

        return $this;
    }

    /**
     * Remove review
     *
     * @param \App\Entity\Review $review
     * @return void
     */
    public function removeReview(\App\Entity\Review $review) {
        $this->reviews->removeElement($review);
    }

    /**
     * Get reviews
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Review>
     */
    public function getReviews() {
        return $this->reviews;
    }

    /**
     * Add favorite
     *
     * @param \App\Entity\Decklist $favorite
     *
     * @return User
     */
    public function addFavorite(\App\Entity\Decklist $favorite) {
        $favorite->addFavorite($this);
        $this->favorites[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite
     *
     * @param \App\Entity\Decklist $favorite
     * @return void
     */
    public function removeFavorite(\App\Entity\Decklist $favorite) {
        $favorite->removeFavorite($this);
        $this->favorites->removeElement($favorite);
    }

    /**
     * Get favorites
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Decklist>
     */
    public function getFavorites() {
        return $this->favorites;
    }

    /**
     * Add vote
     *
     * @param \App\Entity\Decklist $vote
     *
     * @return User
     */
    public function addVote(\App\Entity\Decklist $vote) {
        $vote->addVote($this);
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote
     *
     * @param \App\Entity\Decklist $vote
     * @return void
     */
    public function removeVote(\App\Entity\Decklist $vote) {
        $vote->removeVote($this);
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Decklist>
     */
    public function getVotes() {
        return $this->votes;
    }

    /**
     * Add reviewvote
     *
     * @param \App\Entity\Review $reviewvote
     *
     * @return User
     */
    public function addReviewvote(\App\Entity\Review $reviewvote) {
        $this->reviewvotes[] = $reviewvote;

        return $this;
    }

    /**
     * Remove reviewvote
     *
     * @param \App\Entity\Review $reviewvote
     * @return void
     */
    public function removeReviewvote(\App\Entity\Review $reviewvote) {
        $this->reviewvotes->removeElement($reviewvote);
    }

    /**
     * Get reviewvotes
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Review>
     */
    public function getReviewvotes() {
        return $this->reviewvotes;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\User>
     */
    private $following;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\User>
     */
    private $followers;

    /**
     * Add following
     *
     * @param \App\Entity\User $following
     *
     * @return User
     */
    public function addFollowing(\App\Entity\User $following) {
        $this->following[] = $following;

        return $this;
    }

    /**
     * Remove following
     *
     * @param \App\Entity\User $following
     * @return void
     */
    public function removeFollowing(\App\Entity\User $following) {
        $this->following->removeElement($following);
    }

    /**
     * Get following
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\User>
     */
    public function getFollowing() {
        return $this->following;
    }

    /**
     * Add follower
     *
     * @param \App\Entity\User $follower
     *
     * @return User
     */
    public function addFollower(\App\Entity\User $follower) {
        $this->followers[] = $follower;

        return $this;
    }

    /**
     * Remove follower
     *
     * @param \App\Entity\User $follower
     * @return void
     */
    public function removeFollower(\App\Entity\User $follower) {
        $this->followers->removeElement($follower);
    }

    /**
     * Get followers
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\User>
     */
    public function getFollowers() {
        return $this->followers;
    }
    /**
     * @var string|null
     */
    private $ownedPacks;

    /**
     * Set ownedPacks
     *
     * @param string|null $ownedPacks
     *
     * @return User
     */
    public function setOwnedPacks($ownedPacks) {
        $this->ownedPacks = $ownedPacks;

        return $this;
    }

    /**
     * Get ownedPacks
     *
     * @return string|null
     */
    public function getOwnedPacks() {
        return $this->ownedPacks;
    }

    /**
     * @var string|null
     */
    private $artPreferences;

    /**
     * Set artPreferences (JSON map of card code => preferred pack code)
     *
     * @param string|null $artPreferences
     *
     * @return User
     */
    public function setArtPreferences($artPreferences) {
        $this->artPreferences = $artPreferences;

        return $this;
    }

    /**
     * Get artPreferences
     *
     * @return string|null
     */
    public function getArtPreferences() {
        return $this->artPreferences;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Fellowship>
     */
    private $fellowships;

    /**
     * Add fellowship
     *
     * @param \App\Entity\Fellowship $fellowship
     *
     * @return User
     */
    public function addFellowship(\App\Entity\Fellowship $fellowship) {
        $this->fellowships[] = $fellowship;

        return $this;
    }

    /**
     * Remove fellowship
     *
     * @param \App\Entity\Fellowship $fellowship
     * @return void
     */
    public function removeFellowship(\App\Entity\Fellowship $fellowship) {
        $this->fellowships->removeElement($fellowship);
    }

    /**
     * Get fellowships
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Fellowship>
     */
    public function getFellowships() {
        return $this->fellowships;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection<int, mixed>
     */
    public function getPublicFellowships() {
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
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\FellowshipComment>
     */
    private $fellowship_comments;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Fellowship>
     */
    private $fellowship_favorites;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Fellowship>
     */
    private $fellowship_votes;

    /**
     * Add fellowshipComment
     *
     * @param \App\Entity\FellowshipComment $fellowshipComment
     *
     * @return User
     */
    public function addFellowshipComment(\App\Entity\FellowshipComment $fellowshipComment) {
        $this->fellowship_comments[] = $fellowshipComment;

        return $this;
    }

    /**
     * Remove fellowshipComment
     *
     * @param \App\Entity\FellowshipComment $fellowshipComment
     * @return void
     */
    public function removeFellowshipComment(\App\Entity\FellowshipComment $fellowshipComment) {
        $this->fellowship_comments->removeElement($fellowshipComment);
    }

    /**
     * Get fellowshipComments
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\FellowshipComment>
     */
    public function getFellowshipComments() {
        return $this->fellowship_comments;
    }

    /**
     * Add fellowshipFavorite
     *
     * @param \App\Entity\Fellowship $fellowshipFavorite
     *
     * @return User
     */
    public function addFellowshipFavorite(\App\Entity\Fellowship $fellowshipFavorite) {
        $this->fellowship_favorites[] = $fellowshipFavorite;

        return $this;
    }

    /**
     * Remove fellowshipFavorite
     *
     * @param \App\Entity\Fellowship $fellowshipFavorite
     * @return void
     */
    public function removeFellowshipFavorite(\App\Entity\Fellowship $fellowshipFavorite) {
        $this->fellowship_favorites->removeElement($fellowshipFavorite);
    }

    /**
     * Get fellowshipFavorites
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Fellowship>
     */
    public function getFellowshipFavorites() {
        return $this->fellowship_favorites;
    }

    /**
     * Add fellowshipVote
     *
     * @param \App\Entity\Fellowship $fellowshipVote
     *
     * @return User
     */
    public function addFellowshipVote(\App\Entity\Fellowship $fellowshipVote) {
        $this->fellowship_votes[] = $fellowshipVote;

        return $this;
    }

    /**
     * Remove fellowshipVote
     *
     * @param \App\Entity\Fellowship $fellowshipVote
     * @return void
     */
    public function removeFellowshipVote(\App\Entity\Fellowship $fellowshipVote) {
        $this->fellowship_votes->removeElement($fellowshipVote);
    }

    /**
     * Get fellowshipVotes
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Fellowship>
     */
    public function getFellowshipVotes() {
        return $this->fellowship_votes;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Questlog>
     */
    private $questlogs;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\QuestlogComment>
     */
    private $questlog_comments;

    /**
     * Add questlog
     *
     * @param \App\Entity\Questlog $questlog
     *
     * @return User
     */
    public function addQuestlog(\App\Entity\Questlog $questlog) {
        $this->questlogs[] = $questlog;

        return $this;
    }

    /**
     * Remove questlog
     *
     * @param \App\Entity\Questlog $questlog
     * @return void
     */
    public function removeQuestlog(\App\Entity\Questlog $questlog) {
        $this->questlogs->removeElement($questlog);
    }

    /**
     * Get questlogs
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Questlog>
     */
    public function getQuestlogs() {
        return $this->questlogs;
    }

    /**
     * Add questlogComment
     *
     * @param \App\Entity\QuestlogComment $questlogComment
     *
     * @return User
     */
    public function addQuestlogComment(\App\Entity\QuestlogComment $questlogComment) {
        $this->questlog_comments[] = $questlogComment;

        return $this;
    }

    /**
     * Remove questlogComment
     *
     * @param \App\Entity\QuestlogComment $questlogComment
     * @return void
     */
    public function removeQuestlogComment(\App\Entity\QuestlogComment $questlogComment) {
        $this->questlog_comments->removeElement($questlogComment);
    }

    /**
     * Get questlogComments
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\QuestlogComment>
     */
    public function getQuestlogComments() {
        return $this->questlog_comments;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Questlog>
     */
    private $questlog_favorites;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, \App\Entity\Questlog>
     */
    private $questlog_votes;

    /**
     * Add questlogFavorite
     *
     * @param \App\Entity\Questlog $questlogFavorite
     *
     * @return User
     */
    public function addQuestlogFavorite(\App\Entity\Questlog $questlogFavorite) {
        $this->questlog_favorites[] = $questlogFavorite;

        return $this;
    }

    /**
     * Remove questlogFavorite
     *
     * @param \App\Entity\Questlog $questlogFavorite
     * @return void
     */
    public function removeQuestlogFavorite(\App\Entity\Questlog $questlogFavorite) {
        $this->questlog_favorites->removeElement($questlogFavorite);
    }

    /**
     * Get questlogFavorites
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Questlog>
     */
    public function getQuestlogFavorites() {
        return $this->questlog_favorites;
    }

    /**
     * Add questlogVote
     *
     * @param \App\Entity\Questlog $questlogVote
     *
     * @return User
     */
    public function addQuestlogVote(\App\Entity\Questlog $questlogVote) {
        $this->questlog_votes[] = $questlogVote;

        return $this;
    }

    /**
     * Remove questlogVote
     *
     * @param \App\Entity\Questlog $questlogVote
     * @return void
     */
    public function removeQuestlogVote(\App\Entity\Questlog $questlogVote) {
        $this->questlog_votes->removeElement($questlogVote);
    }

    /**
     * Get questlogVotes
     *
     * @return \Doctrine\Common\Collections\Collection<int, \App\Entity\Questlog>
     */
    public function getQuestlogVotes() {
        return $this->questlog_votes;
    }

    /**
     * @var bool
     */
    protected $locked = false;

    /**
     * Set locked
     *
     * @param boolean $locked
     *
     * @return User
     */
    public function setLocked($locked) {
        $this->locked = (bool) $locked;

        return $this;
    }

    /**
     * Get locked
     *
     * @return boolean
     */
    public function isLocked() {
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
