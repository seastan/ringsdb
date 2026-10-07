<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Encounter.
 */
#[ORM\Entity(repositoryClass: \App\Repository\EncounterRepository::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'entity_region')]
#[ORM\Table(name: 'encounter')]
#[ORM\UniqueConstraint(name: 'encounter_code_idx', columns: ['code'])]
class Encounter implements \JsonSerializable, \Stringable
{
    /**
     * @return array{id: int|null, code: string, name: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->getId(),
            'code' => $this->getCode(),
            'name' => $this->getName(),
        ];
    }

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

    #[ORM\ManyToOne(targetEntity: Pack::class)]
    #[ORM\JoinColumn(name: 'pack_id', referencedColumnName: 'id')]
    private ?Pack $pack = null;

    public function __toString(): string
    {
        return $this->getName();
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
    public function setCode($code): Encounter
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
    public function setName($name): Encounter
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
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): Encounter
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
    public function setDateUpdate($dateUpdate): Encounter
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
     * Set pack.
     */
    public function setPack(?Pack $pack = null): Encounter
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack.
     */
    public function getPack(): ?Pack
    {
        return $this->pack;
    }
}
