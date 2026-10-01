<?php

namespace App\Entity;

/**
 * Scenario.
 */
class Scenario implements \JsonSerializable
{
    public function jsonSerialize()
    {
        $encounters = $this->getEncounters()->toArray();
        $pack = $this->getPack();

        $array = [
            'id' => $this->getId(),
            'code' => $this->getCode(),
            'name' => $this->getName(),
            'nameCanonical' => $this->getNameCanonical(),
            'pack' => $pack ? $pack->getName() : '',
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

        return $array;
    }

    /**
     * @var int
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
    /**
     * @var Pack|null
     */
    private $pack;
    /**
     * @var \Doctrine\Common\Collections\Collection<int, Encounter>
     */
    private $encounters;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->encounters = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set code.
     *
     * @param string $code
     *
     * @return Scenario
     */
    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code.
     *
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return Scenario
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     *
     * @return Scenario
     */
    public function setDateCreation($dateCreation)
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation.
     *
     * @return \DateTime
     */
    public function getDateCreation()
    {
        return $this->dateCreation;
    }

    /**
     * Set dateUpdate.
     *
     * @param \DateTime $dateUpdate
     *
     * @return Scenario
     */
    public function setDateUpdate($dateUpdate)
    {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate.
     *
     * @return \DateTime
     */
    public function getDateUpdate()
    {
        return $this->dateUpdate;
    }

    /**
     * Set pack.
     *
     * @return Scenario
     */
    public function setPack(?Pack $pack = null)
    {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack.
     *
     * @return Pack|null
     */
    public function getPack()
    {
        return $this->pack;
    }

    /**
     * Add encounter.
     *
     * @return Scenario
     */
    public function addEncounter(Encounter $encounter)
    {
        $this->encounters[] = $encounter;

        return $this;
    }

    /**
     * Remove encounter.
     *
     * @return void
     */
    public function removeEncounter(Encounter $encounter)
    {
        $this->encounters->removeElement($encounter);
    }

    /**
     * Get encounters.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Encounter>
     */
    public function getEncounters()
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
     *
     * @return Scenario
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position.
     *
     * @return int
     */
    public function getPosition()
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
     *
     * @return Scenario
     */
    public function setHasEasy($hasEasy)
    {
        $this->hasEasy = $hasEasy;

        return $this;
    }

    /**
     * Get hasEasy.
     *
     * @return bool
     */
    public function getHasEasy()
    {
        return $this->hasEasy;
    }

    /**
     * Set hasNightmare.
     *
     * @param bool $hasNightmare
     *
     * @return Scenario
     */
    public function setHasNightmare($hasNightmare)
    {
        $this->hasNightmare = $hasNightmare;

        return $this;
    }

    /**
     * Get hasNightmare.
     *
     * @return bool
     */
    public function getHasNightmare()
    {
        return $this->hasNightmare;
    }

    /**
     * Set easyCards.
     *
     * @param int $easyCards
     *
     * @return Scenario
     */
    public function setEasyCards($easyCards)
    {
        $this->easyCards = $easyCards;

        return $this;
    }

    /**
     * Get easyCards.
     *
     * @return int
     */
    public function getEasyCards()
    {
        return $this->easyCards;
    }

    /**
     * Set easyEnemies.
     *
     * @param int $easyEnemies
     *
     * @return Scenario
     */
    public function setEasyEnemies($easyEnemies)
    {
        $this->easyEnemies = $easyEnemies;

        return $this;
    }

    /**
     * Get easyEnemies.
     *
     * @return int
     */
    public function getEasyEnemies()
    {
        return $this->easyEnemies;
    }

    /**
     * Set easyLocations.
     *
     * @param int $easyLocations
     *
     * @return Scenario
     */
    public function setEasyLocations($easyLocations)
    {
        $this->easyLocations = $easyLocations;

        return $this;
    }

    /**
     * Get easyLocations.
     *
     * @return int
     */
    public function getEasyLocations()
    {
        return $this->easyLocations;
    }

    /**
     * Set easyTreacheries.
     *
     * @param int $easyTreacheries
     *
     * @return Scenario
     */
    public function setEasyTreacheries($easyTreacheries)
    {
        $this->easyTreacheries = $easyTreacheries;

        return $this;
    }

    /**
     * Get easyTreacheries.
     *
     * @return int
     */
    public function getEasyTreacheries()
    {
        return $this->easyTreacheries;
    }

    /**
     * Set easyObjectiveAllies.
     *
     * @param int $easyObjectiveAllies
     *
     * @return Scenario
     */
    public function setEasyObjectiveAllies($easyObjectiveAllies)
    {
        $this->easyObjectiveAllies = $easyObjectiveAllies;

        return $this;
    }

    /**
     * Get easyObjectiveAllies.
     *
     * @return int
     */
    public function getEasyObjectiveAllies()
    {
        return $this->easyObjectiveAllies;
    }

    /**
     * Set easyObjectiveLocations.
     *
     * @param int $easyObjectiveLocations
     *
     * @return Scenario
     */
    public function setEasyObjectiveLocations($easyObjectiveLocations)
    {
        $this->easyObjectiveLocations = $easyObjectiveLocations;

        return $this;
    }

    /**
     * Get easyObjectiveLocations.
     *
     * @return int
     */
    public function getEasyObjectiveLocations()
    {
        return $this->easyObjectiveLocations;
    }

    /**
     * Set easySurges.
     *
     * @param int $easySurges
     *
     * @return Scenario
     */
    public function setEasySurges($easySurges)
    {
        $this->easySurges = $easySurges;

        return $this;
    }

    /**
     * Get easySurges.
     *
     * @return int
     */
    public function getEasySurges()
    {
        return $this->easySurges;
    }

    /**
     * Set easyShadows.
     *
     * @param int $easyShadows
     *
     * @return Scenario
     */
    public function setEasyShadows($easyShadows)
    {
        $this->easyShadows = $easyShadows;

        return $this;
    }

    /**
     * Get easyShadows.
     *
     * @return int
     */
    public function getEasyShadows()
    {
        return $this->easyShadows;
    }

    /**
     * Set easyEncounterSideQuests.
     *
     * @param int $easyEncounterSideQuests
     *
     * @return Scenario
     */
    public function setEasyEncounterSideQuests($easyEncounterSideQuests)
    {
        $this->easyEncounterSideQuests = $easyEncounterSideQuests;

        return $this;
    }

    /**
     * Get easyEncounterSideQuests.
     *
     * @return int
     */
    public function getEasyEncounterSideQuests()
    {
        return $this->easyEncounterSideQuests;
    }

    /**
     * Set normalCards.
     *
     * @param int $normalCards
     *
     * @return Scenario
     */
    public function setNormalCards($normalCards)
    {
        $this->normalCards = $normalCards;

        return $this;
    }

    /**
     * Get normalCards.
     *
     * @return int
     */
    public function getNormalCards()
    {
        return $this->normalCards;
    }

    /**
     * Set normalEnemies.
     *
     * @param int $normalEnemies
     *
     * @return Scenario
     */
    public function setNormalEnemies($normalEnemies)
    {
        $this->normalEnemies = $normalEnemies;

        return $this;
    }

    /**
     * Get normalEnemies.
     *
     * @return int
     */
    public function getNormalEnemies()
    {
        return $this->normalEnemies;
    }

    /**
     * Set normalLocations.
     *
     * @param int $normalLocations
     *
     * @return Scenario
     */
    public function setNormalLocations($normalLocations)
    {
        $this->normalLocations = $normalLocations;

        return $this;
    }

    /**
     * Get normalLocations.
     *
     * @return int
     */
    public function getNormalLocations()
    {
        return $this->normalLocations;
    }

    /**
     * Set normalTreacheries.
     *
     * @param int $normalTreacheries
     *
     * @return Scenario
     */
    public function setNormalTreacheries($normalTreacheries)
    {
        $this->normalTreacheries = $normalTreacheries;

        return $this;
    }

    /**
     * Get normalTreacheries.
     *
     * @return int
     */
    public function getNormalTreacheries()
    {
        return $this->normalTreacheries;
    }

    /**
     * Set normalObjectiveAllies.
     *
     * @param int $normalObjectiveAllies
     *
     * @return Scenario
     */
    public function setNormalObjectiveAllies($normalObjectiveAllies)
    {
        $this->normalObjectiveAllies = $normalObjectiveAllies;

        return $this;
    }

    /**
     * Get normalObjectiveAllies.
     *
     * @return int
     */
    public function getNormalObjectiveAllies()
    {
        return $this->normalObjectiveAllies;
    }

    /**
     * Set normalObjectiveLocations.
     *
     * @param int $normalObjectiveLocations
     *
     * @return Scenario
     */
    public function setNormalObjectiveLocations($normalObjectiveLocations)
    {
        $this->normalObjectiveLocations = $normalObjectiveLocations;

        return $this;
    }

    /**
     * Get normalObjectiveLocations.
     *
     * @return int
     */
    public function getNormalObjectiveLocations()
    {
        return $this->normalObjectiveLocations;
    }

    /**
     * Set normalSurges.
     *
     * @param int $normalSurges
     *
     * @return Scenario
     */
    public function setNormalSurges($normalSurges)
    {
        $this->normalSurges = $normalSurges;

        return $this;
    }

    /**
     * Get normalSurges.
     *
     * @return int
     */
    public function getNormalSurges()
    {
        return $this->normalSurges;
    }

    /**
     * Set normalShadows.
     *
     * @param int $normalShadows
     *
     * @return Scenario
     */
    public function setNormalShadows($normalShadows)
    {
        $this->normalShadows = $normalShadows;

        return $this;
    }

    /**
     * Get normalShadows.
     *
     * @return int
     */
    public function getNormalShadows()
    {
        return $this->normalShadows;
    }

    /**
     * Set normalEncounterSideQuests.
     *
     * @param int $normalEncounterSideQuests
     *
     * @return Scenario
     */
    public function setNormalEncounterSideQuests($normalEncounterSideQuests)
    {
        $this->normalEncounterSideQuests = $normalEncounterSideQuests;

        return $this;
    }

    /**
     * Get normalEncounterSideQuests.
     *
     * @return int
     */
    public function getNormalEncounterSideQuests()
    {
        return $this->normalEncounterSideQuests;
    }

    /**
     * Set nightmareCards.
     *
     * @param int $nightmareCards
     *
     * @return Scenario
     */
    public function setNightmareCards($nightmareCards)
    {
        $this->nightmareCards = $nightmareCards;

        return $this;
    }

    /**
     * Get nightmareCards.
     *
     * @return int
     */
    public function getNightmareCards()
    {
        return $this->nightmareCards;
    }

    /**
     * Set nightmareEnemies.
     *
     * @param int $nightmareEnemies
     *
     * @return Scenario
     */
    public function setNightmareEnemies($nightmareEnemies)
    {
        $this->nightmareEnemies = $nightmareEnemies;

        return $this;
    }

    /**
     * Get nightmareEnemies.
     *
     * @return int
     */
    public function getNightmareEnemies()
    {
        return $this->nightmareEnemies;
    }

    /**
     * Set nightmareLocations.
     *
     * @param int $nightmareLocations
     *
     * @return Scenario
     */
    public function setNightmareLocations($nightmareLocations)
    {
        $this->nightmareLocations = $nightmareLocations;

        return $this;
    }

    /**
     * Get nightmareLocations.
     *
     * @return int
     */
    public function getNightmareLocations()
    {
        return $this->nightmareLocations;
    }

    /**
     * Set nightmareTreacheries.
     *
     * @param int $nightmareTreacheries
     *
     * @return Scenario
     */
    public function setNightmareTreacheries($nightmareTreacheries)
    {
        $this->nightmareTreacheries = $nightmareTreacheries;

        return $this;
    }

    /**
     * Get nightmareTreacheries.
     *
     * @return int
     */
    public function getNightmareTreacheries()
    {
        return $this->nightmareTreacheries;
    }

    /**
     * Set nightmareObjectiveAllies.
     *
     * @param int $nightmareObjectiveAllies
     *
     * @return Scenario
     */
    public function setNightmareObjectiveAllies($nightmareObjectiveAllies)
    {
        $this->nightmareObjectiveAllies = $nightmareObjectiveAllies;

        return $this;
    }

    /**
     * Get nightmareObjectiveAllies.
     *
     * @return int
     */
    public function getNightmareObjectiveAllies()
    {
        return $this->nightmareObjectiveAllies;
    }

    /**
     * Set nightmareObjectiveLocations.
     *
     * @param int $nightmareObjectiveLocations
     *
     * @return Scenario
     */
    public function setNightmareObjectiveLocations($nightmareObjectiveLocations)
    {
        $this->nightmareObjectiveLocations = $nightmareObjectiveLocations;

        return $this;
    }

    /**
     * Get nightmareObjectiveLocations.
     *
     * @return int
     */
    public function getNightmareObjectiveLocations()
    {
        return $this->nightmareObjectiveLocations;
    }

    /**
     * Set nightmareSurges.
     *
     * @param int $nightmareSurges
     *
     * @return Scenario
     */
    public function setNightmareSurges($nightmareSurges)
    {
        $this->nightmareSurges = $nightmareSurges;

        return $this;
    }

    /**
     * Get nightmareSurges.
     *
     * @return int
     */
    public function getNightmareSurges()
    {
        return $this->nightmareSurges;
    }

    /**
     * Set nightmareShadows.
     *
     * @param int $nightmareShadows
     *
     * @return Scenario
     */
    public function setNightmareShadows($nightmareShadows)
    {
        $this->nightmareShadows = $nightmareShadows;

        return $this;
    }

    /**
     * Get nightmareShadows.
     *
     * @return int
     */
    public function getNightmareShadows()
    {
        return $this->nightmareShadows;
    }

    /**
     * Set nightmareEncounterSideQuests.
     *
     * @param int $nightmareEncounterSideQuests
     *
     * @return Scenario
     */
    public function setNightmareEncounterSideQuests($nightmareEncounterSideQuests)
    {
        $this->nightmareEncounterSideQuests = $nightmareEncounterSideQuests;

        return $this;
    }

    /**
     * Get nightmareEncounterSideQuests.
     *
     * @return int
     */
    public function getNightmareEncounterSideQuests()
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
     *
     * @return Scenario
     */
    public function setEasyObjectives($easyObjectives)
    {
        $this->easyObjectives = $easyObjectives;

        return $this;
    }

    /**
     * Get easyObjectives.
     *
     * @return int
     */
    public function getEasyObjectives()
    {
        return $this->easyObjectives;
    }

    /**
     * Set normalObjectives.
     *
     * @param int $normalObjectives
     *
     * @return Scenario
     */
    public function setNormalObjectives($normalObjectives)
    {
        $this->normalObjectives = $normalObjectives;

        return $this;
    }

    /**
     * Get normalObjectives.
     *
     * @return int
     */
    public function getNormalObjectives()
    {
        return $this->normalObjectives;
    }

    /**
     * Set nightmareObjectives.
     *
     * @param int $nightmareObjectives
     *
     * @return Scenario
     */
    public function setNightmareObjectives($nightmareObjectives)
    {
        $this->nightmareObjectives = $nightmareObjectives;

        return $this;
    }

    /**
     * Get nightmareObjectives.
     *
     * @return int
     */
    public function getNightmareObjectives()
    {
        return $this->nightmareObjectives;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection<int, Questlog>
     */
    private $questlogs;

    /**
     * Add questlog.
     *
     * @return Scenario
     */
    public function addQuestlog(Questlog $questlog)
    {
        $this->questlogs[] = $questlog;

        return $this;
    }

    /**
     * Remove questlog.
     *
     * @return void
     */
    public function removeQuestlog(Questlog $questlog)
    {
        $this->questlogs->removeElement($questlog);
    }

    /**
     * Get questlogs.
     *
     * @return \Doctrine\Common\Collections\Collection<int, Questlog>
     */
    public function getQuestlogs()
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
     *
     * @return Scenario
     */
    public function setNameCanonical($nameCanonical)
    {
        $this->nameCanonical = $nameCanonical;

        return $this;
    }

    /**
     * Get nameCanonical.
     *
     * @return string
     */
    public function getNameCanonical()
    {
        return $this->nameCanonical;
    }
}
