<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Scenario.
 */
class Scenario implements \JsonSerializable
{
    /**
     * @var int|null
     */
    private $id;

    /**
     * @var string
     */
    private $code;

    /**
     * @var string
     */
    private $name;

    /**
     * @var \DateTime
     */
    private $dateCreation;

    /**
     * @var \DateTime
     */
    private $dateUpdate;

    private ?Pack $pack = null;

    /**
     * @var Collection<int, Encounter>
     */
    private $encounters;

    /**
     * @var Collection<int, Questlog>
     */
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
    private $hasEasy;

    /**
     * @var bool
     */
    private $hasNightmare;

    /**
     * @var int
     */
    private $easyCards;

    /**
     * @var int
     */
    private $easyEnemies;

    /**
     * @var int
     */
    private $easyLocations;

    /**
     * @var int
     */
    private $easyTreacheries;

    /**
     * @var int
     */
    private $easyObjectiveAllies;

    /**
     * @var int
     */
    private $easyObjectiveLocations;

    /**
     * @var int
     */
    private $easySurges;

    /**
     * @var int
     */
    private $easyShadows;

    /**
     * @var int
     */
    private $easyEncounterSideQuests;

    /**
     * @var int
     */
    private $normalCards;

    /**
     * @var int
     */
    private $normalEnemies;

    /**
     * @var int
     */
    private $normalLocations;

    /**
     * @var int
     */
    private $normalTreacheries;

    /**
     * @var int
     */
    private $normalObjectiveAllies;

    /**
     * @var int
     */
    private $normalObjectiveLocations;

    /**
     * @var int
     */
    private $normalSurges;

    /**
     * @var int
     */
    private $normalShadows;

    /**
     * @var int
     */
    private $normalEncounterSideQuests;

    /**
     * @var int
     */
    private $nightmareCards;

    /**
     * @var int
     */
    private $nightmareEnemies;

    /**
     * @var int
     */
    private $nightmareLocations;

    /**
     * @var int
     */
    private $nightmareTreacheries;

    /**
     * @var int
     */
    private $nightmareObjectiveAllies;

    /**
     * @var int
     */
    private $nightmareObjectiveLocations;

    /**
     * @var int
     */
    private $nightmareSurges;

    /**
     * @var int
     */
    private $nightmareShadows;

    /**
     * @var int
     */
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
    private $easyObjectives;

    /**
     * @var int
     */
    private $normalObjectives;

    /**
     * @var int
     */
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

    public function jsonSerialize()
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
