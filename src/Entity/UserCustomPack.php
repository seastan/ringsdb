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
    /**
     * @var User
     */
    private $user;
    /**
     * @var string
     */
    private $name;
    /**
     * @var string
     */
    private $code;
    /**
     * @var bool
     */
    private $isEnabled = true;
    /**
     * @var bool
     */
    private $isPublished = false;
    /**
     * @var \DateTime
     */
    private $createdAt;
    /**
     * @var \DateTime
     */
    private $updatedAt;
    /**
     * @var Collection<int, UserCustomPackCard>
     */
    private $cards;

    public function __construct()
    {
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

    /**
     * @return $this
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @return $this
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    public function getIsEnabled(): bool
    {
        return $this->isEnabled;
    }

    /**
     * @return $this
     */
    public function setIsEnabled($isEnabled)
    {
        $this->isEnabled = (bool) $isEnabled;

        return $this;
    }

    public function getIsPublished(): bool
    {
        return $this->isPublished;
    }

    /**
     * @return $this
     */
    public function setIsPublished($isPublished)
    {
        $this->isPublished = (bool) $isPublished;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    /**
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    /**
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
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

    /**
     * @return $this
     */
    public function clearCards()
    {
        $this->cards->clear();

        return $this;
    }

    /**
     * @return $this
     */
    public function addCard(UserCustomPackCard $card)
    {
        $this->cards->add($card);

        return $this;
    }
}
