<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Comment.
 */
class Comment
{
    /**
     * @var int
     */
    private $id;
    /**
     * @var string
     */
    private $text;
    /**
     * @var \DateTime
     */
    private $dateCreation;
    /**
     * @var bool
     */
    private $isHidden;
    /**
     * @var User
     */
    private $user;
    /**
     * @var Decklist
     */
    private $decklist;

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
     * Set text.
     *
     * @param string $text
     *
     * @return Comment
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
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Comment
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
     * Set isHidden.
     *
     * @param bool $isHidden
     *
     * @return Comment
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

    /**
     * Set user.
     *
     * @return Comment
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
     * Set decklist.
     *
     * @return Comment
     */
    public function setDecklist(Decklist $decklist)
    {
        $this->decklist = $decklist;

        return $this;
    }

    /**
     * Get decklist.
     *
     * @return Decklist
     */
    public function getDecklist()
    {
        return $this->decklist;
    }
}
