<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: \App\Repository\CycleRepository::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'entity_region')]
#[ORM\Table(name: 'cycle')]
#[ORM\UniqueConstraint(name: 'cycle_code_idx', columns: ['code'])]
class Cycle
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
     * @var bool
     */
    #[ORM\Column(name: 'is_box', type: 'boolean', nullable: false)]
    private $isBox;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_saga', type: 'boolean', nullable: false)]
    private $isSaga;

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
     * @var Collection<int, Pack>
     */
    #[ORM\Cache(usage: 'NONSTRICT_READ_WRITE')]
    #[ORM\OneToMany(targetEntity: Pack::class, mappedBy: 'cycle')]
    #[ORM\OrderBy(['position' => \SortDirection::Ascending])]
    private $packs;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->packs = new ArrayCollection();
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
    public function setCode($code): Cycle
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
    public function setName($name): Cycle
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
    public function setPosition($position): Cycle
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
     * Set isBox.
     *
     * @param bool $isBox
     */
    public function setIsBox($isBox): Cycle
    {
        $this->isBox = $isBox;

        return $this;
    }

    /**
     * Get isBox.
     */
    public function getIsBox(): bool
    {
        return $this->isBox;
    }

    /**
     * Set isSaga.
     *
     * @param bool $isSaga
     */
    public function setIsSaga($isSaga): Cycle
    {
        $this->isSaga = $isSaga;

        return $this;
    }

    /**
     * Get isSaga.
     */
    public function getIsSaga(): bool
    {
        return $this->isSaga;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Cycle
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
    public function setDateUpdate($dateUpdate): Cycle
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
     * Add pack.
     */
    public function addPack(Pack $pack): Cycle
    {
        $this->packs[] = $pack;

        return $this;
    }

    /**
     * Remove pack.
     */
    public function removePack(Pack $pack): void
    {
        $this->packs->removeElement($pack);
    }

    /**
     * Get packs.
     *
     * @return Collection<int, Pack>
     */
    public function getPacks(): Collection
    {
        return $this->packs;
    }
}
