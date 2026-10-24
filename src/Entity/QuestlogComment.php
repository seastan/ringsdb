<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * QuestlogComment.
 */
#[ORM\Entity(repositoryClass: \App\Repository\QuestlogCommentRepository::class)]
#[ORM\Table(name: 'questlog_comment')]
class QuestlogComment
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(type: 'text')]
    private string $text;

    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'date_creation', type: 'datetime', nullable: false)]
    private \DateTime $dateCreation;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_hidden', type: 'boolean')]
    private $isHidden = false;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'questlog_comments')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Questlog::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'questlog_id', referencedColumnName: 'id', nullable: false)]
    private Questlog $questlog;

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
