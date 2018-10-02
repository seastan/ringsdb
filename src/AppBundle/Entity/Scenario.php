<?php

namespace AppBundle\Entity;

/**
 * Scenario
 */
class Scenario implements \JsonSerializable {
    public function jsonSerialize() {
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
     * @var integer
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
     * @var \AppBundle\Entity\Pack
     */
    private $pack;
    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $encounters;

    /**
     * Constructor
     */
    public function __construct() {
        $this->encounters = new \Doctrine\Common\Collections\ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set code
     *
     * @param string $code
     *
     * @return Scenario
     */
    public function setCode($code) {
        $this->code = $code;

        return $this;
    }

    /**
     * Get code
     *
     * @return string
     */
    public function getCode() {
        return $this->code;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Scenario
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set dateCreation
     *
     * @param \DateTime $dateCreation
     *
     * @return Scenario
     */
    public function setDateCreation($dateCreation) {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Get dateCreation
     *
     * @return \DateTime
     */
    public function getDateCreation() {
        return $this->dateCreation;
    }

    /**
     * Set dateUpdate
     *
     * @param \DateTime $dateUpdate
     *
     * @return Scenario
     */
    public function setDateUpdate($dateUpdate) {
        $this->dateUpdate = $dateUpdate;

        return $this;
    }

    /**
     * Get dateUpdate
     *
     * @return \DateTime
     */
    public function getDateUpdate() {
        return $this->dateUpdate;
    }

    /**
     * Set pack
     *
     * @param \AppBundle\Entity\Pack $pack
     *
     * @return Scenario
     */
    public function setPack(\AppBundle\Entity\Pack $pack = null) {
        $this->pack = $pack;

        return $this;
    }

    /**
     * Get pack
     *
     * @return \AppBundle\Entity\Pack
     */
    public function getPack() {
        return $this->pack;
    }

    /**
     * Add encounter
     *
     * @param \AppBundle\Entity\Encounter $encounter
     *
     * @return Scenario
     */
    public function addEncounter(\AppBundle\Entity\Encounter $encounter) {
        $this->encounters[] = $encounter;

        return $this;
    }

    /**
     * Remove encounter
     *
     * @param \AppBundle\Entity\Encounter $encounter
     */
    public function removeEncounter(\AppBundle\Entity\Encounter $encounter) {
        $this->encounters->removeElement($encounter);
    }

    /**
     * Get encounters
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getEncounters() {
        return $this->encounters;
    }

    /**
     * @var integer
     */
    private $position;

    /**
     * Set position
     *
     * @param integer $position
     *
     * @return Scenario
     */
    public function setPosition($position) {
        $this->position = $position;

        return $this;
    }

    /**
     * Get position
     *
     * @return integer
     */
    public function getPosition() {
        return $this->position;
    }

    /**
     * @var boolean
     */
    private $hasEasy;
    /**
     * @var boolean
     */
    private $hasNightmare;
    /**
     * @var integer
     */
    private $easyCards;
    /**
     * @var integer
     */
    private $easyEnemies;
    /**
     * @var integer
     */
    private $easyLocations;
    /**
     * @var integer
     */
    private $easyTreacheries;
    /**
     * @var integer
     */
    private $easyObjectiveAllies;
    /**
     * @var integer
     */
    private $easyObjectiveLocations;
    /**
     * @var integer
     */
    private $easySurges;
    /**
     * @var integer
     */
    private $easyShadows;
    /**
     * @var integer
     */
    private $easyEncounterSideQuests;
    /**
     * @var integer
     */
    private $normalCards;
    /**
     * @var integer
     */
    private $normalEnemies;
    /**
     * @var integer
     */
    private $normalLocations;
    /**
     * @var integer
     */
    private $normalTreacheries;
    /**
     * @var integer
     */
    private $normalObjectiveAllies;
    /**
     * @var integer
     */
    private $normalObjectiveLocations;
    /**
     * @var integer
     */
    private $normalSurges;
    /**
     * @var integer
     */
    private $normalShadows;
    /**
     * @var integer
     */
    private $normalEncounterSideQuests;
    /**
     * @var integer
     */
    private $nightmareCards;
    /**
     * @var integer
     */
    private $nightmareEnemies;
    /**
     * @var integer
     */
    private $nightmareLocations;
    /**
     * @var integer
     */
    private $nightmareTreacheries;
    /**
     * @var integer
     */
    private $nightmareObjectiveAllies;
    /**
     * @var integer
     */
    private $nightmareObjectiveLocations;
    /**
     * @var integer
     */
    private $nightmareSurges;
    /**
     * @var integer
     */
    private $nightmareShadows;
    /**
     * @var integer
     */
    private $nightmareEncounterSideQuests;

    /**
     * Set hasEasy
     *
     * @param boolean $hasEasy
     *
     * @return Scenario
     */
    public function setHasEasy($hasEasy) {
        $this->hasEasy = $hasEasy;

        return $this;
    }

    /**
     * Get hasEasy
     *
     * @return boolean
     */
    public function getHasEasy() {
        return $this->hasEasy;
    }

    /**
     * Set hasNightmare
     *
     * @param boolean $hasNightmare
     *
     * @return Scenario
     */
    public function setHasNightmare($hasNightmare) {
        $this->hasNightmare = $hasNightmare;

        return $this;
    }

    /**
     * Get hasNightmare
     *
     * @return boolean
     */
    public function getHasNightmare() {
        return $this->hasNightmare;
    }

    /**
     * Set easyCards
     *
     * @param integer $easyCards
     *
     * @return Scenario
     */
    public function setEasyCards($easyCards) {
        $this->easyCards = $easyCards;

        return $this;
    }

    /**
     * Get easyCards
     *
     * @return integer
     */
    public function getEasyCards() {
        return $this->easyCards;
    }

    /**
     * Set easyEnemies
     *
     * @param integer $easyEnemies
     *
     * @return Scenario
     */
    public function setEasyEnemies($easyEnemies) {
        $this->easyEnemies = $easyEnemies;

        return $this;
    }

    /**
     * Get easyEnemies
     *
     * @return integer
     */
    public function getEasyEnemies() {
        return $this->easyEnemies;
    }

    /**
     * Set easyLocations
     *
     * @param integer $easyLocations
     *
     * @return Scenario
     */
    public function setEasyLocations($easyLocations) {
        $this->easyLocations = $easyLocations;

        return $this;
    }

    /**
     * Get easyLocations
     *
     * @return integer
     */
    public function getEasyLocations() {
        return $this->easyLocations;
    }

    /**
     * Set easyTreacheries
     *
     * @param integer $easyTreacheries
     *
     * @return Scenario
     */
    public function setEasyTreacheries($easyTreacheries) {
        $this->easyTreacheries = $easyTreacheries;

        return $this;
    }

    /**
     * Get easyTreacheries
     *
     * @return integer
     */
    public function getEasyTreacheries() {
        return $this->easyTreacheries;
    }

    /**
     * Set easyObjectiveAllies
     *
     * @param integer $easyObjectiveAllies
     *
     * @return Scenario
     */
    public function setEasyObjectiveAllies($easyObjectiveAllies) {
        $this->easyObjectiveAllies = $easyObjectiveAllies;

        return $this;
    }

    /**
     * Get easyObjectiveAllies
     *
     * @return integer
     */
    public function getEasyObjectiveAllies() {
        return $this->easyObjectiveAllies;
    }

    /**
     * Set easyObjectiveLocations
     *
     * @param integer $easyObjectiveLocations
     *
     * @return Scenario
     */
    public function setEasyObjectiveLocations($easyObjectiveLocations) {
        $this->easyObjectiveLocations = $easyObjectiveLocations;

        return $this;
    }

    /**
     * Get easyObjectiveLocations
     *
     * @return integer
     */
    public function getEasyObjectiveLocations() {
        return $this->easyObjectiveLocations;
    }

    /**
     * Set easySurges
     *
     * @param integer $easySurges
     *
     * @return Scenario
     */
    public function setEasySurges($easySurges) {
        $this->easySurges = $easySurges;

        return $this;
    }

    /**
     * Get easySurges
     *
     * @return integer
     */
    public function getEasySurges() {
        return $this->easySurges;
    }

    /**
     * Set easyShadows
     *
     * @param integer $easyShadows
     *
     * @return Scenario
     */
    public function setEasyShadows($easyShadows) {
        $this->easyShadows = $easyShadows;

        return $this;
    }

    /**
     * Get easyShadows
     *
     * @return integer
     */
    public function getEasyShadows() {
        return $this->easyShadows;
    }

    /**
     * Set easyEncounterSideQuests
     *
     * @param integer $easyEncounterSideQuests
     *
     * @return Scenario
     */
    public function setEasyEncounterSideQuests($easyEncounterSideQuests) {
        $this->easyEncounterSideQuests = $easyEncounterSideQuests;

        return $this;
    }

    /**
     * Get easyEncounterSideQuests
     *
     * @return integer
     */
    public function getEasyEncounterSideQuests() {
        return $this->easyEncounterSideQuests;
    }

    /**
     * Set normalCards
     *
     * @param integer $normalCards
     *
     * @return Scenario
     */
    public function setNormalCards($normalCards) {
        $this->normalCards = $normalCards;

        return $this;
    }

    /**
     * Get normalCards
     *
     * @return integer
     */
    public function getNormalCards() {
        return $this->normalCards;
    }

    /**
     * Set normalEnemies
     *
     * @param integer $normalEnemies
     *
     * @return Scenario
     */
    public function setNormalEnemies($normalEnemies) {
        $this->normalEnemies = $normalEnemies;

        return $this;
    }

    /**
     * Get normalEnemies
     *
     * @return integer
     */
    public function getNormalEnemies() {
        return $this->normalEnemies;
    }

    /**
     * Set normalLocations
     *
     * @param integer $normalLocations
     *
     * @return Scenario
     */
    public function setNormalLocations($normalLocations) {
        $this->normalLocations = $normalLocations;

        return $this;
    }

    /**
     * Get normalLocations
     *
     * @return integer
     */
    public function getNormalLocations() {
        return $this->normalLocations;
    }

    /**
     * Set normalTreacheries
     *
     * @param integer $normalTreacheries
     *
     * @return Scenario
     */
    public function setNormalTreacheries($normalTreacheries) {
        $this->normalTreacheries = $normalTreacheries;

        return $this;
    }

    /**
     * Get normalTreacheries
     *
     * @return integer
     */
    public function getNormalTreacheries() {
        return $this->normalTreacheries;
    }

    /**
     * Set normalObjectiveAllies
     *
     * @param integer $normalObjectiveAllies
     *
     * @return Scenario
     */
    public function setNormalObjectiveAllies($normalObjectiveAllies) {
        $this->normalObjectiveAllies = $normalObjectiveAllies;

        return $this;
    }

    /**
     * Get normalObjectiveAllies
     *
     * @return integer
     */
    public function getNormalObjectiveAllies() {
        return $this->normalObjectiveAllies;
    }

    /**
     * Set normalObjectiveLocations
     *
     * @param integer $normalObjectiveLocations
     *
     * @return Scenario
     */
    public function setNormalObjectiveLocations($normalObjectiveLocations) {
        $this->normalObjectiveLocations = $normalObjectiveLocations;

        return $this;
    }

    /**
     * Get normalObjectiveLocations
     *
     * @return integer
     */
    public function getNormalObjectiveLocations() {
        return $this->normalObjectiveLocations;
    }

    /**
     * Set normalSurges
     *
     * @param integer $normalSurges
     *
     * @return Scenario
     */
    public function setNormalSurges($normalSurges) {
        $this->normalSurges = $normalSurges;

        return $this;
    }

    /**
     * Get normalSurges
     *
     * @return integer
     */
    public function getNormalSurges() {
        return $this->normalSurges;
    }

    /**
     * Set normalShadows
     *
     * @param integer $normalShadows
     *
     * @return Scenario
     */
    public function setNormalShadows($normalShadows) {
        $this->normalShadows = $normalShadows;

        return $this;
    }

    /**
     * Get normalShadows
     *
     * @return integer
     */
    public function getNormalShadows() {
        return $this->normalShadows;
    }

    /**
     * Set normalEncounterSideQuests
     *
     * @param integer $normalEncounterSideQuests
     *
     * @return Scenario
     */
    public function setNormalEncounterSideQuests($normalEncounterSideQuests) {
        $this->normalEncounterSideQuests = $normalEncounterSideQuests;

        return $this;
    }

    /**
     * Get normalEncounterSideQuests
     *
     * @return integer
     */
    public function getNormalEncounterSideQuests() {
        return $this->normalEncounterSideQuests;
    }

    /**
     * Set nightmareCards
     *
     * @param integer $nightmareCards
     *
     * @return Scenario
     */
    public function setNightmareCards($nightmareCards) {
        $this->nightmareCards = $nightmareCards;

        return $this;
    }

    /**
     * Get nightmareCards
     *
     * @return integer
     */
    public function getNightmareCards() {
        return $this->nightmareCards;
    }

    /**
     * Set nightmareEnemies
     *
     * @param integer $nightmareEnemies
     *
     * @return Scenario
     */
    public function setNightmareEnemies($nightmareEnemies) {
        $this->nightmareEnemies = $nightmareEnemies;

        return $this;
    }

    /**
     * Get nightmareEnemies
     *
     * @return integer
     */
    public function getNightmareEnemies() {
        return $this->nightmareEnemies;
    }

    /**
     * Set nightmareLocations
     *
     * @param integer $nightmareLocations
     *
     * @return Scenario
     */
    public function setNightmareLocations($nightmareLocations) {
        $this->nightmareLocations = $nightmareLocations;

        return $this;
    }

    /**
     * Get nightmareLocations
     *
     * @return integer
     */
    public function getNightmareLocations() {
        return $this->nightmareLocations;
    }

    /**
     * Set nightmareTreacheries
     *
     * @param integer $nightmareTreacheries
     *
     * @return Scenario
     */
    public function setNightmareTreacheries($nightmareTreacheries) {
        $this->nightmareTreacheries = $nightmareTreacheries;

        return $this;
    }

    /**
     * Get nightmareTreacheries
     *
     * @return integer
     */
    public function getNightmareTreacheries() {
        return $this->nightmareTreacheries;
    }

    /**
     * Set nightmareObjectiveAllies
     *
     * @param integer $nightmareObjectiveAllies
     *
     * @return Scenario
     */
    public function setNightmareObjectiveAllies($nightmareObjectiveAllies) {
        $this->nightmareObjectiveAllies = $nightmareObjectiveAllies;

        return $this;
    }

    /**
     * Get nightmareObjectiveAllies
     *
     * @return integer
     */
    public function getNightmareObjectiveAllies() {
        return $this->nightmareObjectiveAllies;
    }

    /**
     * Set nightmareObjectiveLocations
     *
     * @param integer $nightmareObjectiveLocations
     *
     * @return Scenario
     */
    public function setNightmareObjectiveLocations($nightmareObjectiveLocations) {
        $this->nightmareObjectiveLocations = $nightmareObjectiveLocations;

        return $this;
    }

    /**
     * Get nightmareObjectiveLocations
     *
     * @return integer
     */
    public function getNightmareObjectiveLocations() {
        return $this->nightmareObjectiveLocations;
    }

    /**
     * Set nightmareSurges
     *
     * @param integer $nightmareSurges
     *
     * @return Scenario
     */
    public function setNightmareSurges($nightmareSurges) {
        $this->nightmareSurges = $nightmareSurges;

        return $this;
    }

    /**
     * Get nightmareSurges
     *
     * @return integer
     */
    public function getNightmareSurges() {
        return $this->nightmareSurges;
    }

    /**
     * Set nightmareShadows
     *
     * @param integer $nightmareShadows
     *
     * @return Scenario
     */
    public function setNightmareShadows($nightmareShadows) {
        $this->nightmareShadows = $nightmareShadows;

        return $this;
    }

    /**
     * Get nightmareShadows
     *
     * @return integer
     */
    public function getNightmareShadows() {
        return $this->nightmareShadows;
    }

    /**
     * Set nightmareEncounterSideQuests
     *
     * @param integer $nightmareEncounterSideQuests
     *
     * @return Scenario
     */
    public function setNightmareEncounterSideQuests($nightmareEncounterSideQuests) {
        $this->nightmareEncounterSideQuests = $nightmareEncounterSideQuests;

        return $this;
    }

    /**
     * Get nightmareEncounterSideQuests
     *
     * @return integer
     */
    public function getNightmareEncounterSideQuests() {
        return $this->nightmareEncounterSideQuests;
    }

    /**
     * @var integer
     */
    private $easyObjectives;
    /**
     * @var integer
     */
    private $normalObjectives;
    /**
     * @var integer
     */
    private $nightmareObjectives;

    /**
     * Set easyObjectives
     *
     * @param integer $easyObjectives
     *
     * @return Scenario
     */
    public function setEasyObjectives($easyObjectives) {
        $this->easyObjectives = $easyObjectives;

        return $this;
    }

    /**
     * Get easyObjectives
     *
     * @return integer
     */
    public function getEasyObjectives() {
        return $this->easyObjectives;
    }

    /**
     * Set normalObjectives
     *
     * @param integer $normalObjectives
     *
     * @return Scenario
     */
    public function setNormalObjectives($normalObjectives) {
        $this->normalObjectives = $normalObjectives;

        return $this;
    }

    /**
     * Get normalObjectives
     *
     * @return integer
     */
    public function getNormalObjectives() {
        return $this->normalObjectives;
    }

    /**
     * Set nightmareObjectives
     *
     * @param integer $nightmareObjectives
     *
     * @return Scenario
     */
    public function setNightmareObjectives($nightmareObjectives) {
        $this->nightmareObjectives = $nightmareObjectives;

        return $this;
    }

    /**
     * Get nightmareObjectives
     *
     * @return integer
     */
    public function getNightmareObjectives() {
        return $this->nightmareObjectives;
    }

    /**
     * @var \Doctrine\Common\Collections\Collection
     */
    private $questlogs;

    /**
     * Add questlog
     *
     * @param \AppBundle\Entity\Questlog $questlog
     *
     * @return Scenario
     */
    public function addQuestlog(\AppBundle\Entity\Questlog $questlog) {
        $this->questlogs[] = $questlog;

        return $this;
    }

    /**
     * Remove questlog
     *
     * @param \AppBundle\Entity\Questlog $questlog
     */
    public function removeQuestlog(\AppBundle\Entity\Questlog $questlog) {
        $this->questlogs->removeElement($questlog);
    }

    /**
     * Get questlogs
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getQuestlogs() {
        return $this->questlogs;
    }

    /**
     * @var string
     */
    private $nameCanonical;

    /**
     * Set nameCanonical
     *
     * @param string $nameCanonical
     *
     * @return Scenario
     */
    public function setNameCanonical($nameCanonical) {
        $this->nameCanonical = $nameCanonical;

        return $this;
    }

    /**
     * Get nameCanonical
     *
     * @return string
     */
    public function getNameCanonical() {
        return $this->nameCanonical;
    }
}
