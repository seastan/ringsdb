<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\SphereRepository::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'entity_region')]
#[ORM\Table(name: 'sphere')]
#[ORM\UniqueConstraint(name: 'sphere_code_idx', columns: ['code'])]
class Sphere
{
    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private $code;

    /**
     * @var string
     */
    #[ORM\Column(type: 'string', length: 1024, nullable: false)]
    private $name;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean', nullable: false)]
    private $is_primary;

    /**
     * @var Collection<int, Card>
     */
    #[ORM\OneToMany(mappedBy: 'sphere', targetEntity: Card::class)]
    #[ORM\OrderBy(['position' => 'ASC'])]
    private $cards;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->cards = new ArrayCollection();
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set code.
     *
     * @param string $code
     */
    public function setCode($code): Sphere
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name): Sphere
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Set isPrimary.
     *
     * @param bool $isPrimary
     */
    public function setIsPrimary($isPrimary): Sphere
    {
        $this->is_primary = $isPrimary;

        return $this;
    }

    /**
     * Get isPrimary.
     */
    public function getIsPrimary(): bool
    {
        return $this->is_primary;
    }

    /**
     * Add card.
     */
    public function addCard(Card $card): Sphere
    {
        $this->cards[] = $card;

        return $this;
    }

    /**
     * Remove card.
     */
    public function removeCard(Card $card): void
    {
        $this->cards->removeElement($card);
    }

    /**
     * Get cards.
     *
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        return $this->cards;
    }
}
