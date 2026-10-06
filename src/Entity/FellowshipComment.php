<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * FellowshipComment.
 *
 * @ORM\Entity(repositoryClass="App\Repository\FellowshipCommentRepository")
 * @ORM\Table(name="fellowshipcomment")
 */
class FellowshipComment
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
     * @ORM\Column(name="date_creation", type="datetime", nullable=false)
     * @Gedmo\Timestampable(on="create")
     */
    private \DateTime $dateCreation;

    /**
     * @var \DateTime
     */
    private $dateUpdate;

    /**
     * @ORM\Column(type="text")
     */
    private string $text;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_hidden", type="boolean")
     */
    private $isHidden = false;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="fellowship_comments")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
    private User $user;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Fellowship", inversedBy="comments")
     * @ORM\JoinColumn(name="fellowship_id", referencedColumnName="id", nullable=false)
     */
    private Fellowship $fellowship;

    public function __construct(User $user, Fellowship $fellowship, string $text)
    {
        $this->user = $user;
        $this->fellowship = $fellowship;
        $this->text = $text;
        $this->dateCreation = new \DateTime();
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
     */
    public function setDateCreation(\DateTime $dateCreation): FellowshipComment
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
     */
    public function setText(string $text): FellowshipComment
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
