<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserCustomPackRepository")
 * @ORM\Table(
 *     name="user_custom_pack",
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="ucp_code_idx", columns={"code"})
 *     },
 *     indexes={
 *         @ORM\Index(name="ucp_user_idx", columns={"user_id"})
 *     }
 * )
 */
class UserCustomPack
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
     * @ORM\Column(name="is_enabled", type="boolean", nullable=false, options={"default": true})
     */
    private bool $isEnabled = true;

    /**
     * @ORM\Column(name="is_published", type="boolean", nullable=false, options={"default": false})
     */
    private bool $isPublished = false;

    /**
     * @ORM\Column(name="created_at", type="datetime", nullable=false)
     */
    private \DateTime $createdAt;

    /**
     * @ORM\Column(name="updated_at", type="datetime", nullable=false)
     */
    private \DateTime $updatedAt;

    /**
     * @var Collection<int, UserCustomPackCard>
     *
     * @ORM\OneToMany(targetEntity="App\Entity\UserCustomPackCard", mappedBy="customPack", cascade={"persist", "remove"}, orphanRemoval=true)
     */
    private Collection $cards;

    public function __construct(/**
     * @ORM\ManyToOne(targetEntity="App\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
        private User $user, /**
     * @ORM\Column(type="string", length=255, nullable=false)
     */
        private string $name, /**
     * @ORM\Column(type="string", length=64, nullable=false)
     */
        private string $code
    ) {
        $this->cards = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getIsEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): self
    {
        $this->isEnabled = $isEnabled;

        return $this;
    }

    public function getIsPublished(): bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, UserCustomPackCard>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }

    public function clearCards(): self
    {
        $this->cards->clear();

        return $this;
    }

    public function addCard(UserCustomPackCard $card): self
    {
        $this->cards->add($card);

        return $this;
    }
}
