<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class UserCustomPack
{
    /**
     * @var int|null
     */
    private $id;

    private User $user;

    private string $name;

    private string $code;

    private bool $isEnabled = true;

    private bool $isPublished = false;

    private \DateTime $createdAt;

    private \DateTime $updatedAt;

    /**
     * @var Collection<int, UserCustomPackCard>
     */
    private Collection $cards;

    public function __construct(User $user, string $name, string $code)
    {
        $this->user = $user;
        $this->name = $name;
        $this->code = $code;
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
