<?php

namespace App\Entity;

/**
 * Questlog.
 */
class Questlog
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
     * @var \DateTime
     */
    private $datePlayed;
    /**
     * @var string
     */
    private $questMode;
    /**
     * @var bool
     */
    private $success;
    /**
     * @var int|null
     */
    private $score;
    /**
     * @var int
     */
    private $nbDecks;
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
     * @var bool
     */
    private $isPublic;
    /**
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var \DateTime
     */
    private $dateUpdate;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, QuestlogDeck>
     */
    private $decks;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, QuestlogComment>
     */
    private $comments;
    /**
     * @var User
     */
    private $user;
    /**
     * @var Scenario
     */
    private $scenario;
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
     * @return Questlog
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
     * @return Questlog
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
     * @return Questlog
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
     * @return Questlog
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
     * Set datePlayed.
     *
     * @param \DateTime $datePlayed
     *
     * @return Questlog
     */
    public function setDatePlayed($datePlayed)
    {
        $this->datePlayed = $datePlayed;

        return $this;
    }

    /**
     * Get datePlayed.
     *
     * @return \DateTime
     */
    public function getDatePlayed()
    {
        return $this->datePlayed;
    }

    /**
     * Set questMode.
     *
     * @param string $questMode
     *
     * @return Questlog
     */
    public function setQuestMode($questMode)
    {
        $this->questMode = $questMode;

        return $this;
    }

    /**
     * Get questMode.
     *
     * @return string
     */
    public function getQuestMode()
    {
        return $this->questMode;
    }

    /**
     * Set success.
     *
     * @param bool $success
     *
     * @return Questlog
     */
    public function setSuccess($success)
    {
        $this->success = $success;

        return $this;
    }

    /**
     * Get success.
     *
     * @return bool
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * Set score.
     *
     * @param int|null $score
     *
     * @return Questlog
     */
    public function setScore($score)
    {
        $this->score = $score;

        return $this;
    }

    /**
     * Get score.
     *
     * @return int|null
     */
    public function getScore()
    {
        return $this->score;
    }

    /**
     * Set nbDecks.
     *
     * @param int $nbDecks
     *
     * @return Questlog
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
     * Set nbVotes.
     *
     * @param int $nbVotes
     *
     * @return Questlog
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
     * @return Questlog
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
     * @return Questlog
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
     * Set isPublic.
     *
     * @param bool $isPublic
     *
     * @return Questlog
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
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Questlog
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
     * @return Questlog
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
     * Add deck.
     *
     * @return Questlog
     */
    public function addDeck(QuestlogDeck $deck)
    {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck.
     *
     * @return void
     */
    public function removeDeck(QuestlogDeck $deck)
    {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks.
     *
     * @return \Doctrine\Common\Collections\Collection<int, QuestlogDeck>
     */
    public function getDecks()
    {
        return $this->decks;
    }

    /**
     * Add comment.
     *
     * @return Questlog
     */
    public function addComment(QuestlogComment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     *
     * @return void
     */
    public function removeComment(QuestlogComment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return \Doctrine\Common\Collections\Collection<int, QuestlogComment>
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set user.
     *
     * @return Questlog
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
     * Set scenario.
     *
     * @return Questlog
     */
    public function setScenario(Scenario $scenario)
    {
        $this->scenario = $scenario;

        return $this;
    }

    /**
     * Get scenario.
     *
     * @return Scenario
     */
    public function getScenario()
    {
        return $this->scenario;
    }

    /**
     * Add favorite.
     *
     * @return Questlog
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
     * @return Questlog
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
     * @var \DateTime|null
     */
    private $datePublish;

    /**
     * Set datePublish.
     *
     * @param \DateTime|null $datePublish
     *
     * @return Questlog
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
