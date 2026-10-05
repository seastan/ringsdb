<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Comment.
 */
class Comment
{
    /**
     * @var int|null
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
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set text.
     *
     * @param string $text
     */
    public function setText($text): Comment
    {
        $this->text = $text;

        return $this;
    }

    /**
     * Get text.
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Comment
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
     * Set isHidden.
     *
     * @param bool $isHidden
     */
    public function setIsHidden($isHidden): Comment
    {
        $this->isHidden = $isHidden;

        return $this;
    }

    /**
     * Get isHidden.
     */
    public function getIsHidden(): bool
    {
        return $this->isHidden;
    }

    /**
     * Set user.
     */
    public function setUser(User $user): Comment
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
     * Set decklist.
     */
    public function setDecklist(Decklist $decklist): Comment
    {
        $this->decklist = $decklist;

        return $this;
    }

    /**
     * Get decklist.
     */
    public function getDecklist(): Decklist
    {
        return $this->decklist;
    }
}
