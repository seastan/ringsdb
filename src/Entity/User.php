<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use FOS\UserBundle\Model\User as BaseUser;

/**
 * User.
 */
class User extends BaseUser
{
    /**
     * @return float
     */
    public function getMaxNbDecks()
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
     *
     * @return User
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation.
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set dateUpdate.
     *
     * @param \DateTime $dateUpdate
     *
     * @return User
     */
    public function setDateUpdate($dateUpdate)
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate.
     *
     * @return \DateTime
     */
    public function getDateUpdate()
    {
        return $this->dateUpdate;
    }

    /**
     * Set reputation.
     *
     * @param int $reputation
     *
     * @return User
     */
    public function setReputation($reputation)
    {
        $this->reputation = $reputation;

        return $this;
    }

    /**
     * Get reputation.
     *
     * @return int
     */
    public function getReputation()
    {
        return $this->reputation;
    }

    /**
     * Set resume.
     *
     * @param string|null $resume
     *
     * @return User
     */
    public function setResume($resume)
    {
        $this->resume = $resume;

        return $this;
    }

    /**
     * Get resume.
     *
     * @return string|null
     */
    public function getResume()
    {
        return $this->resume;
    }

    /**
     * Set color.
     *
     * @param string|null $color
     *
     * @return User
     */
    public function setColor($color)
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color.
     *
     * @return string|null
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Set donation.
     *
     * @param int $donation
     *
     * @return User
     */
    public function setDonation($donation)
    {
        $this->donation = $donation;

        return $this;
    }

    /**
     * Get donation.
     *
     * @return int
     */
    public function getDonation()
    {
        return $this->donation;
    }

    /**
     * Set isNotifAuthor.
     *
     * @param bool $isNotifAuthor
     *
     * @return User
     */
    public function setIsNotifAuthor($isNotifAuthor)
    {
        $this->isNotifAuthor = $isNotifAuthor;

        return $this;
    }

    /**
     * Get isNotifAuthor.
     *
     * @return bool
     */
    public function getIsNotifAuthor()
    {
        return $this->isNotifAuthor;
    }

    /**
     * Set isNotifCommenter.
     *
     * @param bool $isNotifCommenter
     *
     * @return User
     */
    public function setIsNotifCommenter($isNotifCommenter)
    {
        $this->isNotifCommenter = $isNotifCommenter;

        return $this;
    }

    /**
     * Get isNotifCommenter.
     *
     * @return bool
     */
    public function getIsNotifCommenter()
    {
        return $this->isNotifCommenter;
    }

    /**
     * Set isNotifMention.
     *
     * @param bool $isNotifMention
     *
     * @return User
     */
    public function setIsNotifMention($isNotifMention)
    {
        $this->isNotifMention = $isNotifMention;

        return $this;
    }

    /**
     * Get isNotifMention.
     *
     * @return bool
     */
    public function getIsNotifMention()
    {
        return $this->isNotifMention;
    }

    /**
     * Set isNotifFollow.
     *
     * @param bool $isNotifFollow
     *
     * @return User
     */
    public function setIsNotifFollow($isNotifFollow)
    {
        $this->isNotifFollow = $isNotifFollow;

        return $this;
    }

    /**
     * Get isNotifFollow.
     *
     * @return bool
     */
    public function getIsNotifFollow()
    {
        return $this->isNotifFollow;
    }

    /**
     * Set isNotifSuccessor.
     *
     * @param bool $isNotifSuccessor
     *
     * @return User
     */
    public function setIsNotifSuccessor($isNotifSuccessor)
    {
        $this->isNotifSuccessor = $isNotifSuccessor;

        return $this;
    }

    /**
     * Get isNotifSuccessor.
     *
     * @return bool
     */
    public function getIsNotifSuccessor()
    {
        return $this->isNotifSuccessor;
    }

    /**
     * Set isShareDecks.
     *
     * @param bool $isShareDecks
     *
     * @return User
     */
    public function setIsShareDecks($isShareDecks)
    {
        $this->isShareDecks = $isShareDecks;

        return $this;
    }

    /**
     * Get isShareDecks.
     *
     * @return bool
     */
    public function getIsShareDecks()
    {
        return $this->isShareDecks;
    }

    /**
     * Set darkMode.
     *
     * @param bool $darkMode
     *
     * @return User
     */
    public function setDarkMode($darkMode)
    {
        $this->darkMode = $darkMode;

        return $this;
    }

    /**
     * Get darkMode.
     *
     * @return bool
     */
    public function getDarkMode()
    {
        return $this->darkMode;
    }

    /**
     * Add deck.
     *
     * @return User
     */
    public function addDeck(Deck $deck)
    {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck.
     *
     * @return void
     */
    public function removeDeck(Deck $deck)
    {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Deck>
     */
    public function getDecks()
    {
        return $this->decks;
    }

    /**
     * Add decklist.
     *
     * @return User
     */
    public function addDecklist(Decklist $decklist)
    {
        $this->decklists[] = $decklist;

        return $this;
    }

    /**
     * Remove decklist.
     *
     * @return void
     */
    public function removeDecklist(Decklist $decklist)
    {
        $this->decklists->removeElement($decklist);
    }

    /**
     * Get decklists.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Decklist>
     */
    public function getDecklists()
    {
        return $this->decklists;
    }

    /**
     * Add comment.
     *
     * @return User
     */
    public function addComment(Comment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     *
     * @return void
     */
    public function removeComment(Comment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Comment>
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Add review.
     *
     * @return User
     */
    public function addReview(Review $review)
    {
        $this->reviews[] = $review;

        return $this;
    }

    /**
     * Remove review.
     *
     * @return void
     */
    public function removeReview(Review $review)
    {
        $this->reviews->removeElement($review);
    }

    /**
     * Get reviews.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Review>
     */
    public function getReviews()
    {
        return $this->reviews;
    }

    /**
     * Add favorite.
     *
     * @return User
     */
    public function addFavorite(Decklist $favorite)
    {
        $favorite->addFavorite($this);
        $this->favorites[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite.
     *
     * @return void
     */
    public function removeFavorite(Decklist $favorite)
    {
        $favorite->removeFavorite($this);
        $this->favorites->removeElement($favorite);
    }

    /**
     * Get favorites.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Decklist>
     */
    public function getFavorites()
    {
        return $this->favorites;
    }

    /**
     * Add vote.
     *
     * @return User
     */
    public function addVote(Decklist $vote)
    {
        $vote->addVote($this);
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote.
     *
     * @return void
     */
    public function removeVote(Decklist $vote)
    {
        $vote->removeVote($this);
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Decklist>
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * Add reviewvote.
     *
     * @return User
     */
    public function addReviewvote(Review $reviewvote)
    {
        $this->reviewvotes[] = $reviewvote;

        return $this;
    }

    /**
     * Remove reviewvote.
     *
     * @return void
     */
    public function removeReviewvote(Review $reviewvote)
    {
        $this->reviewvotes->removeElement($reviewvote);
    }

    /**
     * Get reviewvotes.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Review>
     */
    public function getReviewvotes()
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
     *
     * @return User
     */
    public function addFollowing(User $following)
    {
        $this->following[] = $following;

        return $this;
    }

    /**
     * Remove following.
     *
     * @return void
     */
    public function removeFollowing(User $following)
    {
        $this->following->removeElement($following);
    }

    /**
     * Get following.
     *
     * @return \Doctrine\Common\Collections\Collection<int, User>
     */
    public function getFollowing()
    {
        return $this->following;
    }

    /**
     * Add follower.
     *
     * @return User
     */
    public function addFollower(User $follower)
    {
        $this->followers[] = $follower;

        return $this;
    }

    /**
     * Remove follower.
     *
     * @return void
     */
    public function removeFollower(User $follower)
    {
        $this->followers->removeElement($follower);
    }

    /**
     * Get followers.
     *
     * @return \Doctrine\Common\Collections\Collection<int, User>
     */
    public function getFollowers()
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
     *
     * @return User
     */
    public function setOwnedPacks($ownedPacks)
    {
        $this->ownedPacks = $ownedPacks;

        return $this;
    }

    /**
     * Get ownedPacks.
     *
     * @return string|null
     */
    public function getOwnedPacks()
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
     *
     * @return User
     */
    public function setArtPreferences($artPreferences)
    {
        $this->artPreferences = $artPreferences;

        return $this;
    }

    /**
     * Get artPreferences.
     *
     * @return string|null
     */
    public function getArtPreferences()
    {
        return $this->artPreferences;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, Fellowship>
     */
    private $fellowships;

    /**
     * Add fellowship.
     *
     * @return User
     */
    public function addFellowship(Fellowship $fellowship)
    {
        $this->fellowships[] = $fellowship;

        return $this;
    }

    /**
     * Remove fellowship.
     *
     * @return void
     */
    public function removeFellowship(Fellowship $fellowship)
    {
        $this->fellowships->removeElement($fellowship);
    }

    /**
     * Get fellowships.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Fellowship>
     */
    public function getFellowships()
    {
        return $this->fellowships;
    }

    /**
     * @return ArrayCollection<int, mixed>
     */
    public function getPublicFellowships()
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
     *
     * @return User
     */
    public function addFellowshipComment(FellowshipComment $fellowshipComment)
    {
        $this->fellowship_comments[] = $fellowshipComment;

        return $this;
    }

    /**
     * Remove fellowshipComment.
     *
     * @return void
     */
    public function removeFellowshipComment(FellowshipComment $fellowshipComment)
    {
        $this->fellowship_comments->removeElement($fellowshipComment);
    }

    /**
     * Get fellowshipComments.
     *
     * @return \Doctrine\Common\Collections\Collection<int, FellowshipComment>
     */
    public function getFellowshipComments()
    {
        return $this->fellowship_comments;
    }

    /**
     * Add fellowshipFavorite.
     *
     * @return User
     */
    public function addFellowshipFavorite(Fellowship $fellowshipFavorite)
    {
        $this->fellowship_favorites[] = $fellowshipFavorite;

        return $this;
    }

    /**
     * Remove fellowshipFavorite.
     *
     * @return void
     */
    public function removeFellowshipFavorite(Fellowship $fellowshipFavorite)
    {
        $this->fellowship_favorites->removeElement($fellowshipFavorite);
    }

    /**
     * Get fellowshipFavorites.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Fellowship>
     */
    public function getFellowshipFavorites()
    {
        return $this->fellowship_favorites;
    }

    /**
     * Add fellowshipVote.
     *
     * @return User
     */
    public function addFellowshipVote(Fellowship $fellowshipVote)
    {
        $this->fellowship_votes[] = $fellowshipVote;

        return $this;
    }

    /**
     * Remove fellowshipVote.
     *
     * @return void
     */
    public function removeFellowshipVote(Fellowship $fellowshipVote)
    {
        $this->fellowship_votes->removeElement($fellowshipVote);
    }

    /**
     * Get fellowshipVotes.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Fellowship>
     */
    public function getFellowshipVotes()
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
     *
     * @return User
     */
    public function addQuestlog(Questlog $questlog)
    {
        $this->questlogs[] = $questlog;

        return $this;
    }

    /**
     * Remove questlog.
     *
     * @return void
     */
    public function removeQuestlog(Questlog $questlog)
    {
        $this->questlogs->removeElement($questlog);
    }

    /**
     * Get questlogs.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Questlog>
     */
    public function getQuestlogs()
    {
        return $this->questlogs;
    }

    /**
     * Add questlogComment.
     *
     * @return User
     */
    public function addQuestlogComment(QuestlogComment $questlogComment)
    {
        $this->questlog_comments[] = $questlogComment;

        return $this;
    }

    /**
     * Remove questlogComment.
     *
     * @return void
     */
    public function removeQuestlogComment(QuestlogComment $questlogComment)
    {
        $this->questlog_comments->removeElement($questlogComment);
    }

    /**
     * Get questlogComments.
     *
     * @return \Doctrine\Common\Collections\Collection<int, QuestlogComment>
     */
    public function getQuestlogComments()
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
     *
     * @return User
     */
    public function addQuestlogFavorite(Questlog $questlogFavorite)
    {
        $this->questlog_favorites[] = $questlogFavorite;

        return $this;
    }

    /**
     * Remove questlogFavorite.
     *
     * @return void
     */
    public function removeQuestlogFavorite(Questlog $questlogFavorite)
    {
        $this->questlog_favorites->removeElement($questlogFavorite);
    }

    /**
     * Get questlogFavorites.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Questlog>
     */
    public function getQuestlogFavorites()
    {
        return $this->questlog_favorites;
    }

    /**
     * Add questlogVote.
     *
     * @return User
     */
    public function addQuestlogVote(Questlog $questlogVote)
    {
        $this->questlog_votes[] = $questlogVote;

        return $this;
    }

    /**
     * Remove questlogVote.
     *
     * @return void
     */
    public function removeQuestlogVote(Questlog $questlogVote)
    {
        $this->questlog_votes->removeElement($questlogVote);
    }

    /**
     * Get questlogVotes.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Questlog>
     */
    public function getQuestlogVotes()
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
     *
     * @return User
     */
    public function setLocked($locked)
    {
        $this->locked = (bool) $locked;

        return $this;
    }

    /**
     * Get locked.
     *
     * @return bool
     */
    public function isLocked()
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
