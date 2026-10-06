<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * QuestlogComment.
 *
 * @ORM\Entity(repositoryClass="App\Repository\QuestlogCommentRepository")
 * @ORM\Table(name="questlog_comment")
 */
class QuestlogComment
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
     * @var bool
     *
     * @ORM\Column(name="is_hidden", type="boolean")
     */
    private $isHidden = false;

    public function __construct(/**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="questlog_comments")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false)
     */
        private User $user, /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Questlog", inversedBy="comments")
     * @ORM\JoinColumn(name="questlog_id", referencedColumnName="id", nullable=false)
     */
        private Questlog $questlog, /**
     * @ORM\Column(type="text")
     */
        private string $text
    ) {
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
     * Set text.
     */
    public function setText(string $text): QuestlogComment
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
     */
    public function setDateCreation(\DateTime $dateCreation): QuestlogComment
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
    public function setIsHidden($isHidden): QuestlogComment
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
    public function setUser(User $user): QuestlogComment
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
     * Set questlog.
     */
    public function setQuestlog(Questlog $questlog): QuestlogComment
    {
        $this->questlog = $questlog;

        return $this;
    }

    /**
     * Get questlog.
     */
    public function getQuestlog(): Questlog
    {
        return $this->questlog;
    }
}
