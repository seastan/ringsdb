<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Review.
 */
#[ORM\Entity(repositoryClass: \App\Repository\ReviewRepository::class)]
#[ORM\Table(name: 'review')]
class Review
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var \DateTime
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(name: 'date_creation', type: 'datetime', nullable: false)]
    private $dateCreation;

    /**
     * @var \DateTime
     */
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(name: 'date_update', type: 'datetime', nullable: false)]
    private $dateUpdate;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'date_last_comment', type: 'datetime', nullable: true)]
    private $dateLastComment;

    #[ORM\Column(name: 'text_md', type: 'text', nullable: false)]
    private string $textMd;

    #[ORM\Column(name: 'text_html', type: 'text', nullable: false)]
    private string $textHtml;

    #[ORM\Column(name: 'nb_votes', type: 'smallint', nullable: false)]
    private int $nbVotes;

    /**
     * @var Collection<int, Reviewcomment>
     */
    #[ORM\OneToMany(mappedBy: 'review', targetEntity: Reviewcomment::class, cascade: ['persist'])]
    private $comments;

    #[ORM\ManyToOne(targetEntity: Card::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(name: 'card_id', referencedColumnName: 'id', nullable: false)]
    private Card $card;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reviews')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false)]
    private User $user;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'reviewvotes')]
    #[ORM\JoinTable(name: 'reviewvote', joinColumns: [new ORM\JoinColumn(name: 'review_id', referencedColumnName: 'id')], inverseJoinColumns: [new ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id')])]
    private $votes;

    /**
     * Constructor.
     */
    public function __construct(User $user, Card $card, string $textMd, string $textHtml)
    {
        $this->user = $user;
        $this->card = $card;
        $this->textMd = $textMd;
        $this->textHtml = $textHtml;
        $this->nbVotes = 0;
        $this->comments = new ArrayCollection();
        $this->votes = new ArrayCollection();
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
    public function setDateCreation($dateCreation): Review
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
    public function setDateUpdate($dateUpdate): Review
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
     * Set dateLastComment.
     *
     * @param \DateTime|null $dateLastComment
     */
    public function setDateLastComment($dateLastComment): Review
    {
        $this->dateLastComment = $dateLastComment;

        return $this;
    }

    /**
     * Get dateLastComment.
     */
    public function getDateLastComment(): ?\DateTime
    {
        return $this->dateLastComment;
    }

    /**
     * Set textMd.
     */
    public function setTextMd(string $textMd): Review
    {
        $this->textMd = $textMd;

        return $this;
    }

    /**
     * Get textMd.
     */
    public function getTextMd(): string
    {
        return $this->textMd;
    }

    /**
     * Set textHtml.
     */
    public function setTextHtml(string $textHtml): Review
    {
        $this->textHtml = $textHtml;

        return $this;
    }

    /**
     * Get textHtml.
     */
    public function getTextHtml(): string
    {
        return $this->textHtml;
    }

    /**
     * Set nbVotes.
     */
    public function setNbVotes(int $nbVotes): Review
    {
        $this->nbVotes = $nbVotes;

        return $this;
    }

    /**
     * Get nbVotes.
     */
    public function getNbVotes(): int
    {
        return $this->nbVotes;
    }

    /**
     * Add comment.
     */
    public function addComment(Reviewcomment $comment): Review
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     */
    public function removeComment(Reviewcomment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return Collection<int, Reviewcomment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * Set card.
     */
    public function setCard(Card $card): Review
    {
        $this->card = $card;

        return $this;
    }

    /**
     * Get card.
     */
    public function getCard(): Card
    {
        return $this->card;
    }

    /**
     * Set user.
     */
    public function setUser(User $user): Review
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
     * Add vote.
     */
    public function addVote(User $vote): Review
    {
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote.
     */
    public function removeVote(User $vote): void
    {
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes.
     *
     * @return Collection<int, User>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }
}
