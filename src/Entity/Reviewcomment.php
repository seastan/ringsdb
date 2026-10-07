<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Reviewcomment.
 *
 * @ORM\Entity
 * @ORM\Table(name="reviewcomment")
 */
class Reviewcomment
{
    /**
     * @var int|null
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_creation", type="datetime", nullable=false)
     */
    #[Gedmo\Timestampable(on: 'create')]
    private $dateCreation;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date_update", type="datetime", nullable=false)
     */
    #[Gedmo\Timestampable(on: 'update')]
    private $dateUpdate;

    public function __construct(
        /**
         * @ORM\ManyToOne(targetEntity="App\Entity\User")
         * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
         */
        private User $user,
        /**
         * @ORM\ManyToOne(targetEntity="App\Entity\Review", inversedBy="comments")
         * @ORM\JoinColumn(name="review_id", referencedColumnName="id", nullable=false)
         */
        private Review $review,
        /**
         * @ORM\Column(type="text", nullable=false)
         */
        private string $text
    ) {
    }

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
     */
    public function setText(string $text): Reviewcomment
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
