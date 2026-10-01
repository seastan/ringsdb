<?php

namespace App\Entity;

/**
 * Fellowship.
 */
class Fellowship
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $nameCanonical;
    /**
     * @var string|null
     */
    private $descriptionMd;
    /**
     * @var string|null
     */
    private $descriptionHtml;
    /**
     * @var bool
     */
    private $isPublic;
    /**
     * @var int
     */
    private $nbVotes;
    /**
     * @var int
     */
    private $nbFavorites;
    /**
     * @var int
     */
    private $nbComments;
    /**
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    /**
     * @var \DateTime|null
     */
    private $dateLastComment;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, FellowshipDeck>
     */
    private $decks;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, FellowshipDecklist>
     */
    private $decklists;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, FellowshipComment>
     */
    private $comments;
    /**
     * @var User
     */
    private $user;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, User>
     */
    private $favorites;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, User>
     */
    private $votes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->decks = new \Doctrine\Common\Collections\ArrayCollection();
        $this->decklists = new \Doctrine\Common\Collections\ArrayCollection();
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
        $this->favorites = new \Doctrine\Common\Collections\ArrayCollection();
        $this->votes = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Fellowship
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set nameCanonical.
     *
     * @param string $nameCanonical
     *
     * @return Fellowship
     */
    public function setNameCanonical($nameCanonical)
    {
        $this->nameCanonical = $nameCanonical;

        return $this;
    }

    /**
     * Get nameCanonical.
     *
     * @return string
     */
    public function getNameCanonical()
    {
        return $this->nameCanonical;
    }

    /**
     * Set descriptionMd.
     *
     * @param string|null $descriptionMd
     *
     * @return Fellowship
     */
    public function setDescriptionMd($descriptionMd)
    {
        $this->descriptionMd = $descriptionMd;

        return $this;
    }

    /**
     * Get descriptionMd.
     *
     * @return string|null
     */
    public function getDescriptionMd()
    {
        return $this->descriptionMd;
    }

    /**
     * Set descriptionHtml.
     *
     * @param string|null $descriptionHtml
     *
     * @return Fellowship
     */
    public function setDescriptionHtml($descriptionHtml)
    {
        $this->descriptionHtml = $descriptionHtml;

        return $this;
    }

    /**
     * Get descriptionHtml.
     *
     * @return string|null
     */
    public function getDescriptionHtml()
    {
        return $this->descriptionHtml;
    }

    /**
     * Set isPublic.
     *
     * @param bool $isPublic
     *
     * @return Fellowship
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * Get isPublic.
     *
     * @return bool
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * Set nbVotes.
     *
     * @param int $nbVotes
     *
     * @return Fellowship
     */
    public function setNbVotes($nbVotes)
    {
        $this->nbVotes = $nbVotes;

        return $this;
    }

    /**
     * Get nbVotes.
     *
     * @return int
     */
    public function getNbVotes()
    {
        return $this->nbVotes;
    }

    /**
     * Set nbFavorites.
     *
     * @param int $nbFavorites
     *
     * @return Fellowship
     */
    public function setNbFavorites($nbFavorites)
    {
        $this->nbFavorites = $nbFavorites;

        return $this;
    }

    /**
     * Get nbFavorites.
     *
     * @return int
     */
    public function getNbFavorites()
    {
        return $this->nbFavorites;
    }

    /**
     * Set nbComments.
     *
     * @param int $nbComments
     *
     * @return Fellowship
     */
    public function setNbComments($nbComments)
    {
        $this->nbComments = $nbComments;

        return $this;
    }

    /**
     * Get nbComments.
     *
     * @return int
     */
    public function getNbComments()
    {
        return $this->nbComments;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Fellowship
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
     * @return Fellowship
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
     * Set dateLastComment.
     *
     * @param \DateTime|null $dateLastComment
     *
     * @return Fellowship
     */
    public function setDateLastComment($dateLastComment)
    {
        $this->dateLastComment = $dateLastComment;

        return $this;
    }

    /**
     * Get dateLastComment.
     *
     * @return \DateTime|null
     */
    public function getDateLastComment()
    {
        return $this->dateLastComment;
    }

    /**
     * Add deck.
     *
     * @return Fellowship
     */
    public function addDeck(FellowshipDeck $deck)
    {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck.
     *
     * @return void
     */
    public function removeDeck(FellowshipDeck $deck)
    {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks.
     *
     * @return \Doctrine\Common\Collections\Collection<int, FellowshipDeck>
     */
    public function getDecks()
    {
        return $this->decks;
    }

    /**
     * Add decklist.
     *
     * @return Fellowship
     */
    public function addDecklist(FellowshipDecklist $decklist)
    {
        $this->decklists[] = $decklist;

        return $this;
    }

    /**
     * Remove decklist.
     *
     * @return void
     */
    public function removeDecklist(FellowshipDecklist $decklist)
    {
        $this->decklists->removeElement($decklist);
    }

    /**
     * Get decklists.
     *
     * @return \Doctrine\Common\Collections\Collection<int, FellowshipDecklist>
     */
    public function getDecklists()
    {
        return $this->decklists;
    }

    /**
     * Add comment.
     *
     * @return Fellowship
     */
    public function addComment(FellowshipComment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     *
     * @return void
     */
    public function removeComment(FellowshipComment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return \Doctrine\Common\Collections\Collection<int, FellowshipComment>
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set user.
     *
     * @return Fellowship
     */
    public function setUser(User $user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Add favorite.
     *
     * @return Fellowship
     */
    public function addFavorite(User $favorite)
    {
        $this->favorites[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite.
     *
     * @return void
     */
    public function removeFavorite(User $favorite)
    {
        $this->favorites->removeElement($favorite);
    }

    /**
     * Get favorites.
     *
     * @return \Doctrine\Common\Collections\Collection<int, User>
     */
    public function getFavorites()
    {
        return $this->favorites;
    }

    /**
     * Add vote.
     *
     * @return Fellowship
     */
    public function addVote(User $vote)
    {
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote.
     *
     * @return void
     */
    public function removeVote(User $vote)
    {
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes.
     *
     * @return \Doctrine\Common\Collections\Collection<int, User>
     */
    public function getVotes()
    {
        return $this->votes;
    }

    /**
     * @var int
     */
    private $nbDecks;

    /**
     * Set nbDecks.
     *
     * @param int $nbDecks
     *
     * @return Fellowship
     */
    public function setNbDecks($nbDecks)
    {
        $this->nbDecks = $nbDecks;

        return $this;
    }

    /**
     * Get nbDecks.
     *
     * @return int
     */
    public function getNbDecks()
    {
        return $this->nbDecks;
    }

    /**
     * @var \DateTime|null
     */
    private $datePublish;

    /**
     * Set datePublish.
     *
     * @param \DateTime|null $datePublish
     *
     * @return Fellowship
     */
    public function setDatePublish($datePublish)
    {
        $this->datePublish = $datePublish;

        return $this;
    }

    /**
     * Get datePublish.
     *
     * @return \DateTime|null
     */
    public function getDatePublish()
    {
        return $this->datePublish;
    }
}
