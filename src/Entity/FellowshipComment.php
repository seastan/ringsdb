<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * FellowshipComment.
 */
class FellowshipComment
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
     * @var string
     */
    private $text;
    /**
     * @var User
     */
    private $user;
    /**
     * @var Fellowship
     */
    private $fellowship;

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
     * @return FellowshipComment
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
     * @return FellowshipComment
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
     * Set text.
     *
     * @param string $text
     *
     * @return FellowshipComment
     */
    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text.
     *
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * Set user.
     *
     * @return FellowshipComment
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
     * Set fellowship.
     *
     * @return FellowshipComment
     */
    public function setFellowship(Fellowship $fellowship)
    {
        $this->fellowship = $fellowship;

        return $this;
    }

    /**
     * Get fellowship.
     *
     * @return Fellowship
     */
    public function getFellowship()
    {
        return $this->fellowship;
    }

    /**
     * @var bool
     */
    private $isHidden;

    /**
     * Set isHidden.
     *
     * @param bool $isHidden
     *
     * @return FellowshipComment
     */
    public function setIsHidden($isHidden)
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * Get isHidden.
     *
     * @return bool
     */
    public function getIsHidden()
    {
        return $this->isHidden;
    }
}
