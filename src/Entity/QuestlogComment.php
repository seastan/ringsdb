<?php

declare(strict_types=1);

namespace App\Entity;

/**
 * QuestlogComment.
 */
class QuestlogComment
{
    /**
     * @var int|null
     */
    private $id;

    private string $text;

    private \DateTime $dateCreation;

    /**
     * @var bool
     */
    private $isHidden = false;

    private \App\Entity\User $user;

    private \App\Entity\Questlog $questlog;

    public function __construct(User $user, Questlog $questlog, string $text)
    {
        $this->user = $user;
        $this->questlog = $questlog;
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
     * Set text.
     *
     * @param string $text
     */
    public function setText($text): QuestlogComment
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
    public function setDateCreation($dateCreation): QuestlogComment
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
