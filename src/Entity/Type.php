<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TypeRepository::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'entity_region')]
#[ORM\Table(name: 'type')]
#[ORM\UniqueConstraint(name: 'type_code_idx', columns: ['code'])]
class Type
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
     * @var Collection<int, Card>
     */
    #[ORM\OneToMany(mappedBy: 'type', targetEntity: Card::class)]
    #[ORM\OrderBy(['position' => \SortDirection::Ascending])]
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
    public function setCode($code): Type
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
    public function setName($name): Type
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
     * Add card.
     */
    public function addCard(Card $card): Type
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
