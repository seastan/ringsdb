<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: \App\Repository\PackRepository::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'entity_region')]
#[ORM\Table(name: 'pack')]
#[ORM\Index(columns: ['date_release'], name: 'idx_pack_date_release')]
#[ORM\UniqueConstraint(name: 'pack_code_idx', columns: ['code'])]
class Pack
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
     * @var int
     */
    #[ORM\Column(type: 'smallint', nullable: false)]
    private $position;

    /**
     * @var int
     */
    #[ORM\Column(type: 'smallint', nullable: false)]
    private $size;

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
    #[ORM\Column(name: 'date_release', type: 'date', nullable: true)]
    private $dateRelease;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_repackaged', type: 'boolean', nullable: false, options: ['default' => false])]
    private $isRepackaged = false;

    /**
     * @var Collection<int, CardPrinting>
     */
    #[ORM\OneToMany(mappedBy: 'pack', targetEntity: CardPrinting::class)]
    #[ORM\OrderBy(['position' => \SortDirection::Ascending])]
    private $printings;

    #[ORM\ManyToOne(targetEntity: Cycle::class, inversedBy: 'packs')]
    #[ORM\JoinColumn(name: 'cycle_id', referencedColumnName: 'id', nullable: false)]
    private ?Cycle $cycle = null;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->printings = new ArrayCollection();
    }

    /**
     * Set isRepackaged.
     *
     * @param bool $isRepackaged
     */
    public function setIsRepackaged($isRepackaged): Pack
    {
        $this->isRepackaged = $isRepackaged;

        return $this;
    }

    /**
     * Get isRepackaged.
     */
    public function getIsRepackaged(): bool
    {
        return $this->isRepackaged;
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
    public function setCode($code): Pack
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
    public function setName($name): Pack
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
     * Set position.
     *
     * @param int $position
     */
    public function setPosition($position): Pack
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * Set size.
     *
     * @param int $size
     */
    public function setSize($size): Pack
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Get size.
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Pack
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
    public function setDateUpdate($dateUpdate): Pack
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
     * Set dateRelease.
     *
     * @param \DateTime|null $dateRelease
     */
    public function setDateRelease($dateRelease): Pack
    {
        $this->dateRelease = $dateRelease;

        return $this;
    }

    /**
     * Get dateRelease.
     */
    public function getDateRelease(): ?\DateTime
    {
        return $this->dateRelease;
    }

    /**
     * @return Collection<int, Card>
     */
    public function getCards(): Collection
    {
        /* @phpstan-ignore-next-line return.type */
        return $this->printings->filter(fn ($p): bool => $p->getCard() instanceof Card)->map(fn ($p) => $p->getCard());
    }

    /**
     * Add printing.
     */
    public function addPrinting(CardPrinting $printing): Pack
    {
        $this->printings[] = $printing;

        return $this;
    }

    /**
     * Remove printing.
     */
    public function removePrinting(CardPrinting $printing): void
    {
        $this->printings->removeElement($printing);
    }

    /**
     * Get printings.
     *
     * @return Collection<int, CardPrinting>
     */
    public function getPrintings(): Collection
    {
        return $this->printings;
    }

    /**
     * Set cycle.
     */
    public function setCycle(Cycle $cycle): Pack
    {
        $this->cycle = $cycle;

        return $this;
    }

    /**
     * Get cycle.
     */
    public function getCycle(): ?Cycle
    {
        return $this->cycle;
    }

    /**
     * Returns true is self is later than the argument.
     */
    public function isLaterThan(Pack $latestPack): bool
    {
        if (!$latestPack->getCycle() instanceof Cycle
            || !$this->getCycle() instanceof Cycle) {
            throw new \LogicException('Pack should be an instance of Cycle to be sorted');
        }

        if (!$latestPack->getDateRelease()
            && !$this->getDateRelease()) {
            if ($latestPack->getCycle()->getPosition() === $this->getCycle()->getPosition()) {
                return $latestPack->getCycle()->getPosition() < $this->getCycle()->getPosition();
            }

            return $latestPack->getCycle()->getPosition() < $this->getCycle()->getPosition();
        }

        if ($latestPack->getDateRelease() instanceof \DateTime && $this->getDateRelease() instanceof \DateTime) {
            return $latestPack->getDateRelease() < $this->getDateRelease();
        }

        return !$this->getDateRelease();
    }
}
