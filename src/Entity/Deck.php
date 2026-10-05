<?php

declare(strict_types=1);

namespace App\Entity;

use App\Model\ExportableDeck;
use App\Model\SlotCollectionDecorator;
use App\Model\SlotCollectionInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

class Deck extends ExportableDeck implements \JsonSerializable
{
    /**
     * @return list<array<string, mixed>>
     */
    public function getHistory(): array
    {
        $slots = $this->getSlots();
        $cards = $slots->getContent();
        $sideslots = $this->getSideslots();
        $sidecards = $sideslots->getContent();

        $snapshots = [];
        $changes = $this->getChanges();
        $savedChanges = [];
        $unsavedChanges = [];

        foreach ($changes as $change) {
            if ($change->getIsSaved()) {
                $savedChanges[] = $change;
            } else {
                array_unshift($unsavedChanges, $change);
            }
        }

        $array['unsaved'] = count($unsavedChanges);

        // recreating the versions with the variation info, starting from $preversion
        $preversion = $cards;
        $sidepreversion = $sidecards;
        foreach ($savedChanges as $change) {
            $variation = json_decode($change->getVariation(), true);
            $row = [
                'variation' => $variation,
                'is_saved' => $change->getIsSaved(),
                'version' => $change->getVersion(),
                'content' => [
                    'main' => $preversion,
                    'side' => $sidepreversion,
                ],
                'date_creation' => $change->getDateCreation()->format('c'),
            ];
            array_unshift($snapshots, $row);

            // applying variation to create 'next' (older) preversion
            foreach ($variation[0] as $code => $qty) {
                if (isset($preversion[$code])) {
                    $preversion[$code] -= $qty;
                    if (0 == $preversion[$code]) {
                        unset($preversion[$code]);
                    }
                }
            }

            foreach ($variation[1] as $code => $qty) {
                $preversion[$code] ??= 0;
                $preversion[$code] += $qty;
            }

            $variation[2] ??= [];

            foreach ($variation[2] as $code => $qty) {
                if (isset($sidepreversion[$code])) {
                    $sidepreversion[$code] -= $qty;
                    if (0 == $sidepreversion[$code]) {
                        unset($sidepreversion[$code]);
                    }
                }
            }

            $variation[3] ??= [];

            foreach ($variation[3] as $code => $qty) {
                $sidepreversion[$code] ??= 0;
                $sidepreversion[$code] += $qty;
            }

            ksort($preversion);
            ksort($sidepreversion);
        }

        // add last know version with empty diff
        $row = [
            'variation' => null,
            'is_saved' => true,
            'version' => '0.0',
            'content' => [
                'main' => $preversion,
                'side' => $sidepreversion,
            ],
            'date_creation' => $this->getDateCreation()->format('c'),
        ];
        array_unshift($snapshots, $row);

        // recreating the snapshots with the variation info, starting from $postversion
        $postversion = $cards;
        $sidepostversion = $sidecards;
        foreach ($unsavedChanges as $change) {
            $variation = json_decode($change->getVariation(), true);
            $row = [
                'variation' => $variation,
                'is_saved' => $change->getIsSaved(),
                'version' => $change->getVersion(),
                'date_creation' => $change->getDateCreation()->format('c'),
            ];

            // applying variation to postversion
            foreach ($variation[0] as $code => $qty) {
                $postversion[$code] ??= 0;
                $postversion[$code] += $qty;
            }

            foreach ($variation[1] as $code => $qty) {
                $postversion[$code] -= $qty;
                if (0 == $postversion[$code]) {
                    unset($postversion[$code]);
                }
            }

            $variation[2] ??= [];

            foreach ($variation[2] as $code => $qty) {
                $sidepostversion[$code] ??= 0;
                $sidepostversion[$code] += $qty;
            }

            $variation[3] ??= [];

            foreach ($variation[3] as $code => $qty) {
                $sidepostversion[$code] -= $qty;
                if (0 == $sidepostversion[$code]) {
                    unset($sidepostversion[$code]);
                }
            }

            ksort($postversion);
            ksort($sidepostversion);

            // add postversion with variation that lead to it
            $row['content'] = [
                'main' => $postversion,
                'side' => $sidepostversion,
            ];
            $snapshots[] = $row;
        }

        return $snapshots;
    }

    public function jsonSerialize()
    {
        $array = parent::getArrayExport();
        $array['is_published'] = false;
        $array['problem'] = $this->getProblem();
        $array['tags'] = $this->getTags();
        $array['last_pack'] = $this->getLastPack();
        $array['history'] = $this->getHistory();

        return $array;
    }

    public function getIsUnsaved(): bool
    {
        $changes = $this->getChanges();

        foreach ($changes as $change) {
            if (!$change->getIsSaved()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @var int|null
     */
    private $id;

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
     * @var string|null
     */
    private $descriptionMd;

    /**
     * @var string|null
     */
    private $problem;

    /**
     * @var string|null
     */
    private $tags;

    private int $majorVersion;

    private int $minorVersion;

    /**
     * @var Collection<int, Deckslot>
     */
    private $slots;

    /**
     * @var Collection<int, Decksideslot>
     */
    private $sideslots;

    /**
     * @var Collection<int, Decklist>
     */
    private $children;

    /**
     * @var Collection<int, Deckchange>
     */
    private $changes;

    private User $user;

    private ?Pack $lastPack = null;

    private ?Decklist $parent = null;

    /**
     * @var Collection<int, QuestlogDeck>
     */
    private $questlogs;

    /**
     * @var Collection<int, FellowshipDeck>
     */
    private $fellowships;

    /**
     * Constructor.
     */
    public function __construct(User $user)
    {
        $this->user = $user;
        $this->slots = new ArrayCollection();
        $this->sideslots = new ArrayCollection();
        $this->children = new ArrayCollection();
        $this->changes = new ArrayCollection();
        $this->fellowships = new ArrayCollection();
        $this->questlogs = new ArrayCollection();
        $this->minorVersion = 0;
        $this->majorVersion = 0;
    }

    /**
     * Get id.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Set name.
     *
     * @param string $name
     */
    public function setName($name): Deck
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
    public function setDateCreation($dateCreation): Deck
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
    public function setDateUpdate($dateUpdate): Deck
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
     * Set descriptionMd.
     *
     * @param string|null $descriptionMd
     */
    public function setDescriptionMd($descriptionMd): Deck
    {
        $this->descriptionMd = $descriptionMd;

        return $this;
    }

    /**
     * Get descriptionMd.
     */
    public function getDescriptionMd(): ?string
    {
        return $this->descriptionMd;
    }

    /**
     * Set problem.
     *
     * @param string|null $problem
     */
    public function setProblem($problem): Deck
    {
        $this->problem = $problem;

        return $this;
    }

    /**
     * Get problem.
     */
    public function getProblem(): ?string
    {
        return $this->problem;
    }

    /**
     * Set tags.
     *
     * @param string|null $tags
     */
    public function setTags($tags): Deck
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * Get tags.
     */
    public function getTags(): ?string
    {
        return $this->tags;
    }

    /**
     * Add slot.
     */
    public function addSlot(Deckslot $slot): Deck
    {
        $this->slots[] = $slot;

        return $this;
    }

    /**
     * Remove slot.
     */
    public function removeSlot(Deckslot $slot): void
    {
        $this->slots->removeElement($slot);
    }

    /**
     * Get slots.
     *
     * @return SlotCollectionDecorator<Deckslot>
     */
    public function getSlots(): SlotCollectionDecorator
    {
        return new SlotCollectionDecorator($this->slots);
    }

    /**
     * Add sideslot.
     */
    public function addSideslot(Decksideslot $sideslot): Deck
    {
        $this->sideslots[] = $sideslot;

        return $this;
    }

    /**
     * Remove sideslot.
     */
    public function removeSideslot(Decksideslot $sideslot): void
    {
        $this->sideslots->removeElement($sideslot);
    }

    /**
     * Get sideslots.
     *
     * @return SlotCollectionInterface<Decksideslot>
     */
    public function getSideslots(): SlotCollectionInterface
    {
        return new SlotCollectionDecorator($this->sideslots);
    }

    /**
     * Add child.
     */
    public function addChild(Decklist $child): Deck
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Remove child.
     */
    public function removeChild(Decklist $child): void
    {
        $this->children->removeElement($child);
    }

    /**
     * Get children.
     *
     * @return Collection<int, Decklist>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * Add change.
     */
    public function addChange(Deckchange $change): Deck
    {
        $this->changes[] = $change;

        return $this;
    }

    /**
     * Remove change.
     */
    public function removeChange(Deckchange $change): void
    {
        $this->changes->removeElement($change);
    }

    /**
     * Get changes.
     *
     * @return Collection<int, Deckchange>
     */
    public function getChanges(): Collection
    {
        return $this->changes;
    }

    /**
     * Set user.
     */
    public function setUser(User $user): Deck
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get user.
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * Set lastPack.
     */
    public function setLastPack(?Pack $lastPack = null): Deck
    {
        $this->lastPack = $lastPack;

        return $this;
    }

    /**
     * Get lastPack.
     */
    public function getLastPack(): ?Pack
    {
        return $this->lastPack;
    }

    /**
     * Set parent.
     */
    public function setParent(?Decklist $parent = null): Deck
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get parent.
     */
    public function getParent(): ?Decklist
    {
        return $this->parent;
    }

    /**
     * Set majorVersion.
     *
     * @param int $majorVersion
     */
    public function setMajorVersion($majorVersion): Deck
    {
        $this->majorVersion = $majorVersion;

        return $this;
    }

    /**
     * Get majorVersion.
     */
    public function getMajorVersion(): int
    {
        return $this->majorVersion;
    }

    /**
     * Set minorVersion.
     *
     * @param int $minorVersion
     */
    public function setMinorVersion($minorVersion): Deck
    {
        $this->minorVersion = $minorVersion;

        return $this;
    }

    /**
     * Get minorVersion.
     */
    public function getMinorVersion(): int
    {
        return $this->minorVersion;
    }

    public function getVersion(): string
    {
        return $this->majorVersion.'.'.$this->minorVersion;
    }

    /**
     * Add fellowship.
     */
    public function addFellowship(FellowshipDeck $fellowship): Deck
    {
        $this->fellowships[] = $fellowship;

        return $this;
    }

    /**
     * Remove fellowship.
     */
    public function removeFellowship(FellowshipDeck $fellowship): void
    {
        $this->fellowships->removeElement($fellowship);
    }

    /**
     * Get fellowships.
     *
     * @return Collection<int, FellowshipDeck>
     */
    public function getFellowships(): Collection
    {
        return $this->fellowships;
    }

    /**
     * Get allFellowships.
     *
     * @return array<int, FellowshipDeck|FellowshipDecklist>
     */
    public function getAllFellowships(): array
    {
        $childrenFellowships = $this->getFellowships()->toArray();

        foreach ($this->getChildren() as &$child) {
            $childrenFellowships = array_merge($childrenFellowships, $child->getFellowships()->toArray());
        }

        return $childrenFellowships;
    }

    /**
     * Add questlog.
     */
    public function addQuestlog(QuestlogDeck $questlog): Deck
    {
        $this->questlogs[] = $questlog;

        return $this;
    }

    /**
     * Remove questlog.
     */
    public function removeQuestlog(QuestlogDeck $questlog): void
    {
        $this->questlogs->removeElement($questlog);
    }

    /**
     * Get questlogs.
     *
     * @return Collection<int, QuestlogDeck>
     */
    public function getQuestlogs(): Collection
    {
        return $this->questlogs;
    }

    /**
     * Get allQuestlogs.
     *
     * @return array<int, QuestlogDeck>
     */
    public function getAllQuestlogs(): array
    {
        return $this->getQuestlogs()->toArray();
        /*
            return array_filter($allQuestlogs, function($k) {
                return $k->getQuestlog()->getIsPublic();
            });
        */
    }
}
