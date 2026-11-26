<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Comment.
 */
#[ORM\Entity(repositoryClass: \App\Repository\CommentRepository::class)]
#[ORM\Table(name: 'comment')]
#[ORM\Index(name: 'idx_comment_date_creation', columns: ['date_creation'])]
class Comment
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

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Decklist::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'decklist_id', referencedColumnName: 'id', nullable: false)]
    private Decklist $decklist;

    public function __construct(User $user, Decklist $decklist, string $text)
    {
        $this->user = $user;
        $this->decklist = $decklist;
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
    public function setText(string $text): Comment
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
    public function setDateCreation(\DateTime $dateCreation): Comment
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
