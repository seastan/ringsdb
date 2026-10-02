<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * FellowshipComment.
 */
class FellowshipComment
{
    /**
     * @var int|null
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
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): FellowshipComment
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
    public function setDateUpdate($dateUpdate): FellowshipComment
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
     * Set text.
     *
     * @param string $text
     */
    public function setText($text): FellowshipComment
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
     * Set user.
     */
    public function setUser(User $user): FellowshipComment
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
     * Set fellowship.
     */
    public function setFellowship(Fellowship $fellowship): FellowshipComment
    {
        $this->fellowship = $fellowship;

        return $this;
    }

    /**
     * Get fellowship.
     */
    public function getFellowship(): Fellowship
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
     */
    public function setIsHidden($isHidden): FellowshipComment
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
}
