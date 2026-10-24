<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Scenario.
 */
#[ORM\Entity(repositoryClass: \App\Repository\ScenarioRepository::class)]
#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'entity_region')]
#[ORM\Table(name: 'scenario')]
#[ORM\UniqueConstraint(name: 'scenario_code_idx', columns: ['code'])]
class Scenario implements \JsonSerializable
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
    #[ORM\Column(type: 'string', length: 255, nullable: false)]
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

    /**
     * @var Collection<int, Encounter>
     */
    #[ORM\ManyToMany(targetEntity: Encounter::class)]
    #[ORM\JoinTable(name: 'scenario_encounter', joinColumns: [new ORM\JoinColumn(name: 'scenario_id', referencedColumnName: 'id')], inverseJoinColumns: [new ORM\JoinColumn(name: 'encounter_id', referencedColumnName: 'id')])]
    #[ORM\OrderBy(['pack' => \SortDirection::Ascending])]
    private $encounters;

    /**
     * @var Collection<int, Questlog>
     */
    #[ORM\OneToMany(mappedBy: 'scenario', targetEntity: Questlog::class, cascade: ['persist', 'remove'])]
    private $questlogs;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->encounters = new ArrayCollection();
        $this->questlogs = new ArrayCollection();
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
    public function setCode($code): Scenario
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
    public function setName($name): Scenario
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
    public function setDateCreation($dateCreation): Scenario
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
    public function setDateUpdate($dateUpdate): Scenario
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
    public function setPack(?Pack $pack = null): Scenario
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

    /**
     * Add encounter.
     */
    public function addEncounter(Encounter $encounter): Scenario
    {
        $this->encounters[] = $encounter;

        return $this;
    }

    /**
     * Remove encounter.
     */
    public function removeEncounter(Encounter $encounter): void
    {
        $this->encounters->removeElement($encounter);
    }

    /**
     * Get encounters.
     *
     * @return Collection<int, Encounter>
     */
    public function getEncounters(): Collection
    {
        return $this->encounters;
    }

    /**
     * @var int
     */
    #[ORM\Column(type: 'smallint', nullable: false)]
    private $position;

    /**
     * Set position.
     *
     * @param int $position
     */
    public function setPosition($position): Scenario
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
     * @var bool
     */
    #[ORM\Column(name: 'has_easy', type: 'boolean', nullable: false)]
    private $hasEasy;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'has_nightmare', type: 'boolean', nullable: false)]
    private $hasNightmare;

    /**
     * @var int
     */
    #[ORM\Column(name: 'easy_cards', type: 'smallint', nullable: false)]
    private $easyCards;

    /**
     * @var int
     */
    #[ORM\Column(name: 'easy_enemies', type: 'smallint', nullable: false)]
    private $easyEnemies;

    /**
     * @var int
     */
    #[ORM\Column(name: 'easy_locations', type: 'smallint', nullable: false)]
    private $easyLocations;

    /**
     * @var int
     */
    #[ORM\Column(name: 'easy_treacheries', type: 'smallint', nullable: false)]
    private $easyTreacheries;

    /**
     * @var int
     */
    #[ORM\Column(name: 'easy_objective_allies', type: 'smallint', nullable: false)]
    private $easyObjectiveAllies;

    /**
     * @var int
     */
    #[ORM\Column(name: 'easy_objective_locations', type: 'smallint', nullable: false)]
    private $easyObjectiveLocations;

    /**
     * @var int
     */
    #[ORM\Column(name: 'easy_surges', type: 'smallint', nullable: false)]
    private $easySurges;

    /**
     * @var int
     */
    #[ORM\Column(name: 'easy_shadows', type: 'smallint', nullable: false)]
    private $easyShadows;

    /**
     * @var int
     */
    #[ORM\Column(name: 'easy_encounter_side_quests', type: 'smallint', nullable: false)]
    private $easyEncounterSideQuests;

    /**
     * @var int
     */
    #[ORM\Column(name: 'normal_cards', type: 'smallint', nullable: false)]
    private $normalCards;

    /**
     * @var int
     */
    #[ORM\Column(name: 'normal_enemies', type: 'smallint', nullable: false)]
    private $normalEnemies;

    /**
     * @var int
     */
    #[ORM\Column(name: 'normal_locations', type: 'smallint', nullable: false)]
    private $normalLocations;

    /**
     * @var int
     */
    #[ORM\Column(name: 'normal_treacheries', type: 'smallint', nullable: false)]
    private $normalTreacheries;

    /**
     * @var int
     */
    #[ORM\Column(name: 'normal_objective_allies', type: 'smallint', nullable: false)]
    private $normalObjectiveAllies;

    /**
     * @var int
     */
    #[ORM\Column(name: 'normal_objective_locations', type: 'smallint', nullable: false)]
    private $normalObjectiveLocations;

    /**
     * @var int
     */
    #[ORM\Column(name: 'normal_surges', type: 'smallint', nullable: false)]
    private $normalSurges;

    /**
     * @var int
     */
    #[ORM\Column(name: 'normal_shadows', type: 'smallint', nullable: false)]
    private $normalShadows;

    /**
     * @var int
     */
    #[ORM\Column(name: 'normal_encounter_side_quests', type: 'smallint', nullable: false)]
    private $normalEncounterSideQuests;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nightmare_cards', type: 'smallint', nullable: false)]
    private $nightmareCards;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nightmare_enemies', type: 'smallint', nullable: false)]
    private $nightmareEnemies;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nightmare_locations', type: 'smallint', nullable: false)]
    private $nightmareLocations;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nightmare_treacheries', type: 'smallint', nullable: false)]
    private $nightmareTreacheries;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nightmare_objective_allies', type: 'smallint', nullable: false)]
    private $nightmareObjectiveAllies;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nightmare_objective_locations', type: 'smallint', nullable: false)]
    private $nightmareObjectiveLocations;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nightmare_surges', type: 'smallint', nullable: false)]
    private $nightmareSurges;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nightmare_shadows', type: 'smallint', nullable: false)]
    private $nightmareShadows;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nightmare_encounter_side_quests', type: 'smallint', nullable: false)]
    private $nightmareEncounterSideQuests;

    /**
     * Set hasEasy.
     *
     * @param bool $hasEasy
     */
    public function setHasEasy($hasEasy): Scenario
    {
        $this->hasEasy = $hasEasy;

        return $this;
    }

    /**
     * Get hasEasy.
     */
    public function getHasEasy(): bool
    {
        return $this->hasEasy;
    }

    /**
     * Set hasNightmare.
     *
     * @param bool $hasNightmare
     */
    public function setHasNightmare($hasNightmare): Scenario
    {
        $this->hasNightmare = $hasNightmare;

        return $this;
    }

    /**
     * Get hasNightmare.
     */
    public function getHasNightmare(): bool
    {
        return $this->hasNightmare;
    }

    /**
     * Set easyCards.
     *
     * @param int $easyCards
     */
    public function setEasyCards($easyCards): Scenario
    {
        $this->easyCards = $easyCards;

        return $this;
    }

    /**
     * Get easyCards.
     */
    public function getEasyCards(): int
    {
        return $this->easyCards;
    }

    /**
     * Set easyEnemies.
     *
     * @param int $easyEnemies
     */
    public function setEasyEnemies($easyEnemies): Scenario
    {
        $this->easyEnemies = $easyEnemies;

        return $this;
    }

    /**
     * Get easyEnemies.
     */
    public function getEasyEnemies(): int
    {
        return $this->easyEnemies;
    }

    /**
     * Set easyLocations.
     *
     * @param int $easyLocations
     */
    public function setEasyLocations($easyLocations): Scenario
    {
        $this->easyLocations = $easyLocations;

        return $this;
    }

    /**
     * Get easyLocations.
     */
    public function getEasyLocations(): int
    {
        return $this->easyLocations;
    }

    /**
     * Set easyTreacheries.
     *
     * @param int $easyTreacheries
     */
    public function setEasyTreacheries($easyTreacheries): Scenario
    {
        $this->easyTreacheries = $easyTreacheries;

        return $this;
    }

    /**
     * Get easyTreacheries.
     */
    public function getEasyTreacheries(): int
    {
        return $this->easyTreacheries;
    }

    /**
     * Set easyObjectiveAllies.
     *
     * @param int $easyObjectiveAllies
     */
    public function setEasyObjectiveAllies($easyObjectiveAllies): Scenario
    {
        $this->easyObjectiveAllies = $easyObjectiveAllies;

        return $this;
    }

    /**
     * Get easyObjectiveAllies.
     */
    public function getEasyObjectiveAllies(): int
    {
        return $this->easyObjectiveAllies;
    }

    /**
     * Set easyObjectiveLocations.
     *
     * @param int $easyObjectiveLocations
     */
    public function setEasyObjectiveLocations($easyObjectiveLocations): Scenario
    {
        $this->easyObjectiveLocations = $easyObjectiveLocations;

        return $this;
    }

    /**
     * Get easyObjectiveLocations.
     */
    public function getEasyObjectiveLocations(): int
    {
        return $this->easyObjectiveLocations;
    }

    /**
     * Set easySurges.
     *
     * @param int $easySurges
     */
    public function setEasySurges($easySurges): Scenario
    {
        $this->easySurges = $easySurges;

        return $this;
    }

    /**
     * Get easySurges.
     */
    public function getEasySurges(): int
    {
        return $this->easySurges;
    }

    /**
     * Set easyShadows.
     *
     * @param int $easyShadows
     */
    public function setEasyShadows($easyShadows): Scenario
    {
        $this->easyShadows = $easyShadows;

        return $this;
    }

    /**
     * Get easyShadows.
     */
    public function getEasyShadows(): int
    {
        return $this->easyShadows;
    }

    /**
     * Set easyEncounterSideQuests.
     *
     * @param int $easyEncounterSideQuests
     */
    public function setEasyEncounterSideQuests($easyEncounterSideQuests): Scenario
    {
        $this->easyEncounterSideQuests = $easyEncounterSideQuests;

        return $this;
    }

    /**
     * Get easyEncounterSideQuests.
     */
    public function getEasyEncounterSideQuests(): int
    {
        return $this->easyEncounterSideQuests;
    }

    /**
     * Set normalCards.
     *
     * @param int $normalCards
     */
    public function setNormalCards($normalCards): Scenario
    {
        $this->normalCards = $normalCards;

        return $this;
    }

    /**
     * Get normalCards.
     */
    public function getNormalCards(): int
    {
        return $this->normalCards;
    }

    /**
     * Set normalEnemies.
     *
     * @param int $normalEnemies
     */
    public function setNormalEnemies($normalEnemies): Scenario
    {
        $this->normalEnemies = $normalEnemies;

        return $this;
    }

    /**
     * Get normalEnemies.
     */
    public function getNormalEnemies(): int
    {
        return $this->normalEnemies;
    }

    /**
     * Set normalLocations.
     *
     * @param int $normalLocations
     */
    public function setNormalLocations($normalLocations): Scenario
    {
        $this->normalLocations = $normalLocations;

        return $this;
    }

    /**
     * Get normalLocations.
     */
    public function getNormalLocations(): int
    {
        return $this->normalLocations;
    }

    /**
     * Set normalTreacheries.
     *
     * @param int $normalTreacheries
     */
    public function setNormalTreacheries($normalTreacheries): Scenario
    {
        $this->normalTreacheries = $normalTreacheries;

        return $this;
    }

    /**
     * Get normalTreacheries.
     */
    public function getNormalTreacheries(): int
    {
        return $this->normalTreacheries;
    }

    /**
     * Set normalObjectiveAllies.
     *
     * @param int $normalObjectiveAllies
     */
    public function setNormalObjectiveAllies($normalObjectiveAllies): Scenario
    {
        $this->normalObjectiveAllies = $normalObjectiveAllies;

        return $this;
    }

    /**
     * Get normalObjectiveAllies.
     */
    public function getNormalObjectiveAllies(): int
    {
        return $this->normalObjectiveAllies;
    }

    /**
     * Set normalObjectiveLocations.
     *
     * @param int $normalObjectiveLocations
     */
    public function setNormalObjectiveLocations($normalObjectiveLocations): Scenario
    {
        $this->normalObjectiveLocations = $normalObjectiveLocations;

        return $this;
    }

    /**
     * Get normalObjectiveLocations.
     */
    public function getNormalObjectiveLocations(): int
    {
        return $this->normalObjectiveLocations;
    }

    /**
     * Set normalSurges.
     *
     * @param int $normalSurges
     */
    public function setNormalSurges($normalSurges): Scenario
    {
        $this->normalSurges = $normalSurges;

        return $this;
    }

    /**
     * Get normalSurges.
     */
    public function getNormalSurges(): int
    {
        return $this->normalSurges;
    }

    /**
     * Set normalShadows.
     *
     * @param int $normalShadows
     */
    public function setNormalShadows($normalShadows): Scenario
    {
        $this->normalShadows = $normalShadows;

        return $this;
    }

    /**
     * Get normalShadows.
     */
    public function getNormalShadows(): int
    {
        return $this->normalShadows;
    }

    /**
     * Set normalEncounterSideQuests.
     *
     * @param int $normalEncounterSideQuests
     */
    public function setNormalEncounterSideQuests($normalEncounterSideQuests): Scenario
    {
        $this->normalEncounterSideQuests = $normalEncounterSideQuests;

        return $this;
    }

    /**
     * Get normalEncounterSideQuests.
     */
    public function getNormalEncounterSideQuests(): int
    {
        return $this->normalEncounterSideQuests;
    }

    /**
     * Set nightmareCards.
     *
     * @param int $nightmareCards
     */
    public function setNightmareCards($nightmareCards): Scenario
    {
        $this->nightmareCards = $nightmareCards;

        return $this;
    }

    /**
     * Get nightmareCards.
     */
    public function getNightmareCards(): int
    {
        return $this->nightmareCards;
    }

    /**
     * Set nightmareEnemies.
     *
     * @param int $nightmareEnemies
     */
    public function setNightmareEnemies($nightmareEnemies): Scenario
    {
        $this->nightmareEnemies = $nightmareEnemies;

        return $this;
    }

    /**
     * Get nightmareEnemies.
     */
    public function getNightmareEnemies(): int
    {
        return $this->nightmareEnemies;
    }

    /**
     * Set nightmareLocations.
     *
     * @param int $nightmareLocations
     */
    public function setNightmareLocations($nightmareLocations): Scenario
    {
        $this->nightmareLocations = $nightmareLocations;

        return $this;
    }

    /**
     * Get nightmareLocations.
     */
    public function getNightmareLocations(): int
    {
        return $this->nightmareLocations;
    }

    /**
     * Set nightmareTreacheries.
     *
     * @param int $nightmareTreacheries
     */
    public function setNightmareTreacheries($nightmareTreacheries): Scenario
    {
        $this->nightmareTreacheries = $nightmareTreacheries;

        return $this;
    }

    /**
     * Get nightmareTreacheries.
     */
    public function getNightmareTreacheries(): int
    {
        return $this->nightmareTreacheries;
    }

    /**
     * Set nightmareObjectiveAllies.
     *
     * @param int $nightmareObjectiveAllies
     */
    public function setNightmareObjectiveAllies($nightmareObjectiveAllies): Scenario
    {
        $this->nightmareObjectiveAllies = $nightmareObjectiveAllies;

        return $this;
    }

    /**
     * Get nightmareObjectiveAllies.
     */
    public function getNightmareObjectiveAllies(): int
    {
        return $this->nightmareObjectiveAllies;
    }

    /**
     * Set nightmareObjectiveLocations.
     *
     * @param int $nightmareObjectiveLocations
     */
    public function setNightmareObjectiveLocations($nightmareObjectiveLocations): Scenario
    {
        $this->nightmareObjectiveLocations = $nightmareObjectiveLocations;

        return $this;
    }

    /**
     * Get nightmareObjectiveLocations.
     */
    public function getNightmareObjectiveLocations(): int
    {
        return $this->nightmareObjectiveLocations;
    }

    /**
     * Set nightmareSurges.
     *
     * @param int $nightmareSurges
     */
    public function setNightmareSurges($nightmareSurges): Scenario
    {
        $this->nightmareSurges = $nightmareSurges;

        return $this;
    }

    /**
     * Get nightmareSurges.
     */
    public function getNightmareSurges(): int
    {
        return $this->nightmareSurges;
    }

    /**
     * Set nightmareShadows.
     *
     * @param int $nightmareShadows
     */
    public function setNightmareShadows($nightmareShadows): Scenario
    {
        $this->nightmareShadows = $nightmareShadows;

        return $this;
    }

    /**
     * Get nightmareShadows.
     */
    public function getNightmareShadows(): int
    {
        return $this->nightmareShadows;
    }

    /**
     * Set nightmareEncounterSideQuests.
     *
     * @param int $nightmareEncounterSideQuests
     */
    public function setNightmareEncounterSideQuests($nightmareEncounterSideQuests): Scenario
    {
        $this->nightmareEncounterSideQuests = $nightmareEncounterSideQuests;

        return $this;
    }

    /**
     * Get nightmareEncounterSideQuests.
     */
    public function getNightmareEncounterSideQuests(): int
    {
        return $this->nightmareEncounterSideQuests;
    }

    /**
     * @var int
     */
    #[ORM\Column(name: 'easy_objectives', type: 'smallint', nullable: false)]
    private $easyObjectives;

    /**
     * @var int
     */
    #[ORM\Column(name: 'normal_objectives', type: 'smallint', nullable: false)]
    private $normalObjectives;

    /**
     * @var int
     */
    #[ORM\Column(name: 'nightmare_objectives', type: 'smallint', nullable: false)]
    private $nightmareObjectives;

    /**
     * Set easyObjectives.
     *
     * @param int $easyObjectives
     */
    public function setEasyObjectives($easyObjectives): Scenario
    {
        $this->easyObjectives = $easyObjectives;

        return $this;
    }

    /**
     * Get easyObjectives.
     */
    public function getEasyObjectives(): int
    {
        return $this->easyObjectives;
    }

    /**
     * Set normalObjectives.
     *
     * @param int $normalObjectives
     */
    public function setNormalObjectives($normalObjectives): Scenario
    {
        $this->normalObjectives = $normalObjectives;

        return $this;
    }

    /**
     * Get normalObjectives.
     */
    public function getNormalObjectives(): int
    {
        return $this->normalObjectives;
    }

    /**
     * Set nightmareObjectives.
     *
     * @param int $nightmareObjectives
     */
    public function setNightmareObjectives($nightmareObjectives): Scenario
    {
        $this->nightmareObjectives = $nightmareObjectives;

        return $this;
    }

    /**
     * Get nightmareObjectives.
     */
    public function getNightmareObjectives(): int
    {
        return $this->nightmareObjectives;
    }

    /**
     * Add questlog.
     */
    public function addQuestlog(Questlog $questlog): Scenario
    {
        $this->questlogs[] = $questlog;

        return $this;
    }

    /**
     * Remove questlog.
     */
    public function removeQuestlog(Questlog $questlog): void
    {
        $this->questlogs->removeElement($questlog);
    }

    /**
     * Get questlogs.
     *
     * @return Collection<int, Questlog>
     */
    public function getQuestlogs(): Collection
    {
        return $this->questlogs;
    }

    /**
     * @var string
     */
    #[ORM\Column(name: 'name_canonical', type: 'string', length: 255)]
    private $nameCanonical;

    /**
     * Set nameCanonical.
     *
     * @param string $nameCanonical
     */
    public function setNameCanonical($nameCanonical): Scenario
    {
        $this->nameCanonical = $nameCanonical;

        return $this;
    }

    /**
     * Get nameCanonical.
     */
    public function getNameCanonical(): string
    {
        return $this->nameCanonical;
    }

    /**
     * @return array{
     *     id: int|null,
     *     code: string,
     *     name: string,
     *     nameCanonical: string,
     *     pack: string,
     *     date_creation: string,
     *     date_update: string,
     *     encounters: array<int, Encounter>,
     *     has_easy: bool,
     *     has_nightmare: bool,
     *     easy_cards: int,
     *     easy_enemies: int,
     *     easy_locations: int,
     *     easy_treacheries: int,
     *     easy_shadows: int,
     *     easy_objectives: int,
     *     easy_objective_allies: int,
     *     easy_objective_locations: int,
     *     easy_surges: int,
     *     easy_encounter_side_quests: int,
     *     normal_cards: int,
     *     normal_enemies: int,
     *     normal_locations: int,
     *     normal_treacheries: int,
     *     normal_shadows: int,
     *     normal_objectives: int,
     *     normal_objective_allies: int,
     *     normal_objective_locations: int,
     *     normal_surges: int,
     *     normal_encounter_side_quests: int,
     *     nightmare_cards: int,
     *     nightmare_enemies: int,
     *     nightmare_locations: int,
     *     nightmare_treacheries: int,
     *     nightmare_shadows: int,
     *     nightmare_objectives: int,
     *     nightmare_objective_allies: int,
     *     nightmare_objective_locations: int,
     *     nightmare_surges: int,
     *     nightmare_encounter_side_quests: int,
     * }
     */
    public function jsonSerialize(): array
    {
        $encounters = $this->getEncounters()->toArray();
        $pack = $this->getPack();

        return [
            'id' => $this->getId(),
            'code' => $this->getCode(),
            'name' => $this->getName(),
            'nameCanonical' => $this->getNameCanonical(),
            'pack' => $pack instanceof Pack ? $pack->getName() : '',
            'date_creation' => $this->getDateCreation()->format('c'),
            'date_update' => $this->getDateUpdate()->format('c'),
            'encounters' => $encounters,
            'has_easy' => $this->getHasEasy(),
            'has_nightmare' => $this->getHasNightmare(),
            'easy_cards' => $this->getEasyCards(),
            'easy_enemies' => $this->getEasyEnemies(),
            'easy_locations' => $this->getEasyLocations(),
            'easy_treacheries' => $this->getEasyTreacheries(),
            'easy_shadows' => $this->getEasyShadows(),
            'easy_objectives' => $this->getEasyObjectives(),
            'easy_objective_allies' => $this->getEasyObjectiveAllies(),
            'easy_objective_locations' => $this->getEasyObjectiveLocations(),
            'easy_surges' => $this->getEasySurges(),
            'easy_encounter_side_quests' => $this->getEasyEncounterSideQuests(),

            'normal_cards' => $this->getNormalCards(),
            'normal_enemies' => $this->getNormalEnemies(),
            'normal_locations' => $this->getNormalLocations(),
            'normal_treacheries' => $this->getNormalTreacheries(),
            'normal_shadows' => $this->getNormalShadows(),
            'normal_objectives' => $this->getNormalObjectives(),
            'normal_objective_allies' => $this->getNormalObjectiveAllies(),
            'normal_objective_locations' => $this->getNormalObjectiveLocations(),
            'normal_surges' => $this->getNormalSurges(),
            'normal_encounter_side_quests' => $this->getNormalEncounterSideQuests(),

            'nightmare_cards' => $this->getNightmareCards(),
            'nightmare_enemies' => $this->getNightmareEnemies(),
            'nightmare_locations' => $this->getNightmareLocations(),
            'nightmare_treacheries' => $this->getNightmareTreacheries(),
            'nightmare_shadows' => $this->getNightmareShadows(),
            'nightmare_objectives' => $this->getNightmareObjectives(),
            'nightmare_objective_allies' => $this->getNightmareObjectiveAllies(),
            'nightmare_objective_locations' => $this->getNightmareObjectiveLocations(),
            'nightmare_surges' => $this->getNightmareSurges(),
            'nightmare_encounter_side_quests' => $this->getNightmareEncounterSideQuests(),
        ];
    }
}
