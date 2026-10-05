<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * Reviewcomment.
 */
class Reviewcomment
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
     * @var Review
     */
    private $review;

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
    public function setDateCreation($dateCreation): Reviewcomment
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
    public function setDateUpdate($dateUpdate): Reviewcomment
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
    public function setText($text): Reviewcomment
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
    public function setUser(User $user): Reviewcomment
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
     * Set review.
     */
    public function setReview(Review $review): Reviewcomment
    {
        $this->review = $review;

        return $this;
    }

    /**
     * Get review.
     */
    public function getReview(): Review
    {
        return $this->review;
    }
}
