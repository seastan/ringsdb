<?php

namespace App\Entity;

/**
 * Review.
 */
class Review
{
    /**
     * @var int
     */
    private $id;
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
     * @var string
     */
    private $textMd;
    /**
     * @var string
     */
    private $textHtml;
    /**
     * @var int
     */
    private $nbVotes;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Reviewcomment>
     */
    private $comments;
    /**
     * @var Card
     */
    private $card;
    /**
     * @var User
     */
    private $user;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, User>
     */
    private $votes;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->comments = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Review
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
     * @return Review
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
     * @return Review
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
     * Set textMd.
     *
     * @param string $textMd
     *
     * @return Review
     */
    public function setTextMd($textMd)
    {
        $this->textMd = $textMd;

        return $this;
    }

    /**
     * Get textMd.
     *
     * @return string
     */
    public function getTextMd()
    {
        return $this->textMd;
    }

    /**
     * Set textHtml.
     *
     * @param string $textHtml
     *
     * @return Review
     */
    public function setTextHtml($textHtml)
    {
        $this->textHtml = $textHtml;

        return $this;
    }

    /**
     * Get textHtml.
     *
     * @return string
     */
    public function getTextHtml()
    {
        return $this->textHtml;
    }

    /**
     * Set nbVotes.
     *
     * @param int $nbVotes
     *
     * @return Review
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
     * Add comment.
     *
     * @return Review
     */
    public function addComment(Reviewcomment $comment)
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     *
     * @return void
     */
    public function removeComment(Reviewcomment $comment)
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Reviewcomment>
     */
    public function getComments()
    {
        return $this->comments;
    }

    /**
     * Set card.
     *
     * @return Review
     */
    public function setCard(Card $card)
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card.
     *
     * @return Card
     */
    public function getCard()
    {
        return $this->card;
    }

    /**
     * Set user.
     *
     * @return Review
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
     * Add vote.
     *
     * @return Review
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
}
