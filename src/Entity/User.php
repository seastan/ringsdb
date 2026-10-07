<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\EquatableInterface;
use Symfony\Component\Security\Core\User\LegacyPasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * User.
 */
#[ORM\Entity(repositoryClass: \App\Repository\UserRepository::class)]
#[UniqueEntity(fields: 'usernameCanonical', message: 'The username is already used.', errorPath: 'username', groups: ['Registration', 'Profile'])]
#[UniqueEntity(fields: 'emailCanonical', message: 'The email is already used.', errorPath: 'email', groups: ['Registration', 'Profile'])]
#[ORM\Table(name: 'user')]
class User implements UserInterface, LegacyPasswordAuthenticatedUserInterface, EquatableInterface, \Stringable
{
    public const ROLE_DEFAULT = 'ROLE_USER';

    public const ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    /**
     * @var int|null
     */
    #[ORM\Id]
    #[ORM\Column(type: 'integer')]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    private $id;

    #[ORM\Column(type: 'string', length: 180)]
    #[Assert\NotBlank(message: 'Please enter a username.', groups: ['Registration', 'Profile'])]
    #[Assert\Length(min: 2, max: 180, minMessage: 'The username is too short.', maxMessage: 'The username is too long.', groups: ['Registration', 'Profile'])]
    private ?string $username = null;

    /**
     * Lowercased username, for the case-insensitive lookups (login, registration uniqueness).
     */
    #[ORM\Column(name: 'username_canonical', type: 'string', length: 180, unique: true)]
    private ?string $usernameCanonical = null;

    #[ORM\Column(type: 'string', length: 180)]
    #[Assert\NotBlank(message: 'Please enter an email.', groups: ['Registration', 'Profile'])]
    #[Assert\Length(min: 2, max: 180, minMessage: 'The email is too short.', maxMessage: 'The email is too long.', groups: ['Registration', 'Profile'])]
    #[Assert\Email(message: 'The email is not valid.', groups: ['Registration', 'Profile'])]
    private ?string $email = null;

    #[ORM\Column(name: 'email_canonical', type: 'string', length: 180, unique: true)]
    private ?string $emailCanonical = null;

    /**
     * False until the registration is confirmed by email.
     */
    #[ORM\Column(type: 'boolean')]
    private bool $enabled = false;

    /**
     * The per-user salt of the legacy sha512 hashes (see security.yaml).
     */
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $salt = null;

    #[ORM\Column(type: 'string')]
    private ?string $password = null;

    /**
     * Not persisted: hashed into $password by UserPasswordUpdater.
     */
    #[Assert\NotBlank(message: 'Please enter a password.', groups: ['Registration', 'ResetPassword', 'ChangePassword'])]
    #[Assert\Length(min: 2, max: 4096, minMessage: 'The password is too short.', groups: ['Registration', 'Profile', 'ResetPassword', 'ChangePassword'])]
    private ?string $plainPassword = null;

    #[ORM\Column(name: 'last_login', type: 'datetime', nullable: true)]
    private ?\DateTime $lastLogin = null;

    /**
     * The token of the registration confirmation link, then of the password reset link.
     */
    #[ORM\Column(name: 'confirmation_token', type: 'string', length: 180, unique: true, nullable: true)]
    private ?string $confirmationToken = null;

    #[ORM\Column(name: 'password_requested_at', type: 'datetime', nullable: true)]
    private ?\DateTime $passwordRequestedAt = null;

    /**
     * @var string[]
     */
    #[ORM\Column(type: 'array')]
    private array $roles = [];

    public function getMaxNbDecks(): float
    {
        return 5 * (100 + floor($this->reputation / 10));
    }

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

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $reputation;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private $resume;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $color;

    #[ORM\Column(type: 'integer', nullable: false)]
    private int $donation;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_notif_author', type: 'boolean', nullable: false, options: ['default' => true])]
    private $isNotifAuthor = true;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_notif_commenter', type: 'boolean', nullable: false, options: ['default' => true])]
    private $isNotifCommenter = true;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_notif_mention', type: 'boolean', nullable: false, options: ['default' => true])]
    private $isNotifMention = true;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_notif_follow', type: 'boolean', nullable: false, options: ['default' => true])]
    private $isNotifFollow = true;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_notif_successor', type: 'boolean', nullable: false, options: ['default' => true])]
    private $isNotifSuccessor = true;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'is_share_decks', type: 'boolean', nullable: false, options: ['default' => false])]
    private $isShareDecks = false;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'dark_mode', type: 'boolean', nullable: false, options: ['default' => false])]
    private $darkMode = false;

    /**
     * @var Collection<int, Deck>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Deck::class, cascade: ['remove'])]
    #[ORM\OrderBy(['dateUpdate' => 'DESC'])]
    private $decks;

    /**
     * @var Collection<int, Decklist>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Decklist::class)]
    private $decklists;

    /**
     * @var Collection<int, Comment>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Comment::class)]
    #[ORM\OrderBy(['dateCreation' => 'DESC'])]
    private $comments;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Review::class)]
    #[ORM\OrderBy(['dateCreation' => 'DESC'])]
    private $reviews;

    /**
     * @var Collection<int, Decklist>
     */
    #[ORM\ManyToMany(targetEntity: Decklist::class, mappedBy: 'favorites', cascade: ['remove'])]
    private $favorites;

    /**
     * @var Collection<int, Decklist>
     */
    #[ORM\ManyToMany(targetEntity: Decklist::class, mappedBy: 'votes', cascade: ['remove'])]
    private $votes;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\ManyToMany(targetEntity: Review::class, mappedBy: 'votes', cascade: ['remove'])]
    private $reviewvotes;

    public function __construct()
    {
        $this->reputation = 1;
        $this->donation = 0;
        $this->decks = new ArrayCollection();
        $this->decklists = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->favorites = new ArrayCollection();
        $this->votes = new ArrayCollection();
        $this->reviewvotes = new ArrayCollection();
        $this->following = new ArrayCollection();
        $this->followers = new ArrayCollection();
        $this->fellowships = new ArrayCollection();
        $this->fellowship_comments = new ArrayCollection();
        $this->fellowship_favorites = new ArrayCollection();
        $this->fellowship_votes = new ArrayCollection();
        $this->questlogs = new ArrayCollection();
        $this->questlog_comments = new ArrayCollection();
        $this->questlog_favorites = new ArrayCollection();
        $this->questlog_votes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->getUsername();
    }

    /**
     * What the session stores (the user is reloaded by id on each request): the format of
     * FOSUserBundle's base class, so that the sessions opened before its removal stay valid.
     *
     * @return array<int, bool|int|string|null>
     */
    public function __serialize(): array
    {
        return [
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->emailCanonical,
        ];
    }

    /**
     * @param mixed[] $data
     */
    public function __unserialize(array $data): void
    {
        [
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->enabled,
            $this->id,
            $this->email,
            $this->emailCanonical
        ] = $data;
    }

    /**
     * Lowercases a username or an email, for the *Canonical columns.
     */
    public static function canonicalize(string $string): string
    {
        return mb_strtolower($string);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getUsername(): string
    {
        return (string) $this->username;
    }

    /**
     * Also sets the canonical username.
     */
    public function setUsername(?string $username): User
    {
        $this->username = $username;
        $this->usernameCanonical = null === $username ? null : self::canonicalize($username);

        return $this;
    }

    public function getUsernameCanonical(): ?string
    {
        return $this->usernameCanonical;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    /**
     * Also sets the canonical email.
     */
    public function setEmail(?string $email): User
    {
        $this->email = $email;
        $this->emailCanonical = null === $email ? null : self::canonicalize($email);

        return $this;
    }

    public function getEmailCanonical(): ?string
    {
        return $this->emailCanonical;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): User
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(?string $salt): User
    {
        $this->salt = $salt;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): User
    {
        $this->password = $password;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): User
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }

    public function eraseCredentials(): void
    {
        $this->plainPassword = null;
    }

    public function getLastLogin(): ?\DateTime
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTime $lastLogin): User
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getConfirmationToken(): ?string
    {
        return $this->confirmationToken;
    }

    public function setConfirmationToken(?string $confirmationToken): User
    {
        $this->confirmationToken = $confirmationToken;

        return $this;
    }

    public function getPasswordRequestedAt(): ?\DateTime
    {
        return $this->passwordRequestedAt;
    }

    public function setPasswordRequestedAt(?\DateTime $passwordRequestedAt): User
    {
        $this->passwordRequestedAt = $passwordRequestedAt;

        return $this;
    }

    /**
     * Whether a password reset was requested less than $ttl seconds ago.
     */
    public function isPasswordRequestNonExpired(int $ttl): bool
    {
        return $this->passwordRequestedAt instanceof \DateTime
            && $this->passwordRequestedAt->getTimestamp() + $ttl > time();
    }

    /**
     * The stored roles, plus ROLE_USER.
     *
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = self::ROLE_DEFAULT;

        return array_values(array_unique($roles));
    }

    /**
     * @param string[] $roles
     */
    public function setRoles(array $roles): User
    {
        $this->roles = [];
        foreach ($roles as $role) {
            $this->addRole($role);
        }

        return $this;
    }

    public function addRole(string $role): User
    {
        $role = strtoupper($role);
        if (self::ROLE_DEFAULT !== $role && !in_array($role, $this->roles, true)) {
            $this->roles[] = $role;
        }

        return $this;
    }

    /**
     * A user whose password (or username) changed in the database is logged out.
     */
    public function isEqualTo(UserInterface $user): bool
    {
        return $user instanceof self
            && $this->password === $user->password
            && $this->salt === $user->salt
            && $this->username === $user->username;
    }

    /**
     * Set dateCreation.
     *
     * @param \DateTime $dateCreation
     */
    public function setDateCreation($dateCreation): User
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
    public function setDateUpdate($dateUpdate): User
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
     * Set reputation.
     */
    public function setReputation(int $reputation): User
    {
        $this->reputation = $reputation;

        return $this;
    }

    /**
     * Get reputation.
     */
    public function getReputation(): int
    {
        return $this->reputation;
    }

    /**
     * Set resume.
     *
     * @param string|null $resume
     */
    public function setResume($resume): User
    {
        $this->resume = $resume;

        return $this;
    }

    /**
     * Get resume.
     */
    public function getResume(): ?string
    {
        return $this->resume;
    }

    /**
     * Set color.
     *
     * @param string|null $color
     */
    public function setColor($color): User
    {
        $this->color = $color;

        return $this;
    }

    /**
     * Get color.
     */
    public function getColor(): ?string
    {
        return $this->color;
    }

    /**
     * Set donation.
     */
    public function setDonation(int $donation): User
    {
        $this->donation = $donation;

        return $this;
    }

    /**
     * Get donation.
     */
    public function getDonation(): int
    {
        return $this->donation;
    }

    /**
     * Set isNotifAuthor.
     *
     * @param bool $isNotifAuthor
     */
    public function setIsNotifAuthor($isNotifAuthor): User
    {
        $this->isNotifAuthor = $isNotifAuthor;

        return $this;
    }

    /**
     * Get isNotifAuthor.
     */
    public function getIsNotifAuthor(): bool
    {
        return $this->isNotifAuthor;
    }

    /**
     * Set isNotifCommenter.
     *
     * @param bool $isNotifCommenter
     */
    public function setIsNotifCommenter($isNotifCommenter): User
    {
        $this->isNotifCommenter = $isNotifCommenter;

        return $this;
    }

    /**
     * Get isNotifCommenter.
     */
    public function getIsNotifCommenter(): bool
    {
        return $this->isNotifCommenter;
    }

    /**
     * Set isNotifMention.
     *
     * @param bool $isNotifMention
     */
    public function setIsNotifMention($isNotifMention): User
    {
        $this->isNotifMention = $isNotifMention;

        return $this;
    }

    /**
     * Get isNotifMention.
     */
    public function getIsNotifMention(): bool
    {
        return $this->isNotifMention;
    }

    /**
     * Set isNotifFollow.
     *
     * @param bool $isNotifFollow
     */
    public function setIsNotifFollow($isNotifFollow): User
    {
        $this->isNotifFollow = $isNotifFollow;

        return $this;
    }

    /**
     * Get isNotifFollow.
     */
    public function getIsNotifFollow(): bool
    {
        return $this->isNotifFollow;
    }

    /**
     * Set isNotifSuccessor.
     *
     * @param bool $isNotifSuccessor
     */
    public function setIsNotifSuccessor($isNotifSuccessor): User
    {
        $this->isNotifSuccessor = $isNotifSuccessor;

        return $this;
    }

    /**
     * Get isNotifSuccessor.
     */
    public function getIsNotifSuccessor(): bool
    {
        return $this->isNotifSuccessor;
    }

    /**
     * Set isShareDecks.
     *
     * @param bool $isShareDecks
     */
    public function setIsShareDecks($isShareDecks): User
    {
        $this->isShareDecks = $isShareDecks;

        return $this;
    }

    /**
     * Get isShareDecks.
     */
    public function getIsShareDecks(): bool
    {
        return $this->isShareDecks;
    }

    /**
     * Set darkMode.
     *
     * @param bool $darkMode
     */
    public function setDarkMode($darkMode): User
    {
        $this->darkMode = $darkMode;

        return $this;
    }

    /**
     * Get darkMode.
     */
    public function getDarkMode(): bool
    {
        return $this->darkMode;
    }

    /**
     * Add deck.
     */
    public function addDeck(Deck $deck): User
    {
        $this->decks[] = $deck;

        return $this;
    }

    /**
     * Remove deck.
     */
    public function removeDeck(Deck $deck): void
    {
        $this->decks->removeElement($deck);
    }

    /**
     * Get decks.
     *
     * @return Collection<int, Deck>
     */
    public function getDecks(): Collection
    {
        return $this->decks;
    }

    /**
     * Add decklist.
     */
    public function addDecklist(Decklist $decklist): User
    {
        $this->decklists[] = $decklist;

        return $this;
    }

    /**
     * Remove decklist.
     */
    public function removeDecklist(Decklist $decklist): void
    {
        $this->decklists->removeElement($decklist);
    }

    /**
     * Get decklists.
     *
     * @return Collection<int, Decklist>
     */
    public function getDecklists(): Collection
    {
        return $this->decklists;
    }

    /**
     * Add comment.
     */
    public function addComment(Comment $comment): User
    {
        $this->comments[] = $comment;

        return $this;
    }

    /**
     * Remove comment.
     */
    public function removeComment(Comment $comment): void
    {
        $this->comments->removeElement($comment);
    }

    /**
     * Get comments.
     *
     * @return Collection<int, Comment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    /**
     * Add review.
     */
    public function addReview(Review $review): User
    {
        $this->reviews[] = $review;

        return $this;
    }

    /**
     * Remove review.
     */
    public function removeReview(Review $review): void
    {
        $this->reviews->removeElement($review);
    }

    /**
     * Get reviews.
     *
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    /**
     * Add favorite.
     */
    public function addFavorite(Decklist $favorite): User
    {
        $favorite->addFavorite($this);
        $this->favorites[] = $favorite;

        return $this;
    }

    /**
     * Remove favorite.
     */
    public function removeFavorite(Decklist $favorite): void
    {
        $favorite->removeFavorite($this);
        $this->favorites->removeElement($favorite);
    }

    /**
     * Get favorites.
     *
     * @return Collection<int, Decklist>
     */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }

    /**
     * Add vote.
     */
    public function addVote(Decklist $vote): User
    {
        $vote->addVote($this);
        $this->votes[] = $vote;

        return $this;
    }

    /**
     * Remove vote.
     */
    public function removeVote(Decklist $vote): void
    {
        $vote->removeVote($this);
        $this->votes->removeElement($vote);
    }

    /**
     * Get votes.
     *
     * @return Collection<int, Decklist>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    /**
     * Add reviewvote.
     */
    public function addReviewvote(Review $reviewvote): User
    {
        $this->reviewvotes[] = $reviewvote;

        return $this;
    }

    /**
     * Remove reviewvote.
     */
    public function removeReviewvote(Review $reviewvote): void
    {
        $this->reviewvotes->removeElement($reviewvote);
    }

    /**
     * Get reviewvotes.
     *
     * @return Collection<int, Review>
     */
    public function getReviewvotes(): Collection
    {
        return $this->reviewvotes;
    }

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'followers')]
    private $following;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'following')]
    #[ORM\JoinTable(name: 'follow', joinColumns: [new ORM\JoinColumn(name: 'following_id', referencedColumnName: 'id')], inverseJoinColumns: [new ORM\JoinColumn(name: 'follower_id', referencedColumnName: 'id')])]
    private $followers;

    /**
     * Add following.
     */
    public function addFollowing(User $following): User
    {
        $this->following[] = $following;

        return $this;
    }

    /**
     * Remove following.
     */
    public function removeFollowing(User $following): void
    {
        $this->following->removeElement($following);
    }

    /**
     * Get following.
     *
     * @return Collection<int, User>
     */
    public function getFollowing(): Collection
    {
        return $this->following;
    }

    /**
     * Add follower.
     */
    public function addFollower(User $follower): User
    {
        $this->followers[] = $follower;

        return $this;
    }

    /**
     * Remove follower.
     */
    public function removeFollower(User $follower): void
    {
        $this->followers->removeElement($follower);
    }

    /**
     * Get followers.
     *
     * @return Collection<int, User>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private $ownedPacks;

    /**
     * Set ownedPacks.
     *
     * @param string|null $ownedPacks
     */
    public function setOwnedPacks($ownedPacks): User
    {
        $this->ownedPacks = $ownedPacks;

        return $this;
    }

    /**
     * Get ownedPacks.
     */
    public function getOwnedPacks(): ?string
    {
        return $this->ownedPacks;
    }

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'art_preferences', type: 'text', nullable: true)]
    private $artPreferences;

    /**
     * Set artPreferences (JSON map of card code => preferred pack code).
     *
     * @param string|null $artPreferences
     */
    public function setArtPreferences($artPreferences): User
    {
        $this->artPreferences = $artPreferences;

        return $this;
    }

    /**
     * Get artPreferences.
     */
    public function getArtPreferences(): ?string
    {
        return $this->artPreferences;
    }

    /**
     * @var Collection<int, Fellowship>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Fellowship::class, cascade: ['remove'])]
    #[ORM\OrderBy(['dateUpdate' => 'DESC'])]
    private $fellowships;

    /**
     * Add fellowship.
     */
    public function addFellowship(Fellowship $fellowship): User
    {
        $this->fellowships[] = $fellowship;

        return $this;
    }

    /**
     * Remove fellowship.
     */
    public function removeFellowship(Fellowship $fellowship): void
    {
        $this->fellowships->removeElement($fellowship);
    }

    /**
     * Get fellowships.
     *
     * @return Collection<int, Fellowship>
     */
    public function getFellowships(): Collection
    {
        return $this->fellowships;
    }

    /**
     * @return ArrayCollection<int, mixed>
     */
    public function getPublicFellowships(): ArrayCollection
    {
        $publicFellowships = [];

        foreach ($this->fellowships as $fellowship) {
            /* @var $fellowship \App\Entity\Fellowship */
            if ($fellowship->getIsPublic()) {
                $publicFellowships[] = $fellowship;
            }
        }

        return new ArrayCollection($publicFellowships);
    }

    /**
     * @var Collection<int, FellowshipComment>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: FellowshipComment::class)]
    #[ORM\OrderBy(['dateCreation' => 'DESC'])]
    private $fellowship_comments;

    /**
     * @var Collection<int, Fellowship>
     */
    #[ORM\ManyToMany(targetEntity: Fellowship::class, mappedBy: 'favorites', cascade: ['remove'])]
    private $fellowship_favorites;

    /**
     * @var Collection<int, Fellowship>
     */
    #[ORM\ManyToMany(targetEntity: Fellowship::class, mappedBy: 'votes', cascade: ['remove'])]
    private $fellowship_votes;

    /**
     * Add fellowshipComment.
     */
    public function addFellowshipComment(FellowshipComment $fellowshipComment): User
    {
        $this->fellowship_comments[] = $fellowshipComment;

        return $this;
    }

    /**
     * Remove fellowshipComment.
     */
    public function removeFellowshipComment(FellowshipComment $fellowshipComment): void
    {
        $this->fellowship_comments->removeElement($fellowshipComment);
    }

    /**
     * Get fellowshipComments.
     *
     * @return Collection<int, FellowshipComment>
     */
    public function getFellowshipComments(): Collection
    {
        return $this->fellowship_comments;
    }

    /**
     * Add fellowshipFavorite.
     */
    public function addFellowshipFavorite(Fellowship $fellowshipFavorite): User
    {
        $this->fellowship_favorites[] = $fellowshipFavorite;

        return $this;
    }

    /**
     * Remove fellowshipFavorite.
     */
    public function removeFellowshipFavorite(Fellowship $fellowshipFavorite): void
    {
        $this->fellowship_favorites->removeElement($fellowshipFavorite);
    }

    /**
     * Get fellowshipFavorites.
     *
     * @return Collection<int, Fellowship>
     */
    public function getFellowshipFavorites(): Collection
    {
        return $this->fellowship_favorites;
    }

    /**
     * Add fellowshipVote.
     */
    public function addFellowshipVote(Fellowship $fellowshipVote): User
    {
        $this->fellowship_votes[] = $fellowshipVote;

        return $this;
    }

    /**
     * Remove fellowshipVote.
     */
    public function removeFellowshipVote(Fellowship $fellowshipVote): void
    {
        $this->fellowship_votes->removeElement($fellowshipVote);
    }

    /**
     * Get fellowshipVotes.
     *
     * @return Collection<int, Fellowship>
     */
    public function getFellowshipVotes(): Collection
    {
        return $this->fellowship_votes;
    }

    /**
     * @var Collection<int, Questlog>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Questlog::class, cascade: ['remove'])]
    #[ORM\OrderBy(['dateUpdate' => 'DESC'])]
    private $questlogs;

    /**
     * @var Collection<int, QuestlogComment>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: QuestlogComment::class)]
    #[ORM\OrderBy(['dateCreation' => 'DESC'])]
    private $questlog_comments;

    /**
     * Add questlog.
     */
    public function addQuestlog(Questlog $questlog): User
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
     * Add questlogComment.
     */
    public function addQuestlogComment(QuestlogComment $questlogComment): User
    {
        $this->questlog_comments[] = $questlogComment;

        return $this;
    }

    /**
     * Remove questlogComment.
     */
    public function removeQuestlogComment(QuestlogComment $questlogComment): void
    {
        $this->questlog_comments->removeElement($questlogComment);
    }

    /**
     * Get questlogComments.
     *
     * @return Collection<int, QuestlogComment>
     */
    public function getQuestlogComments(): Collection
    {
        return $this->questlog_comments;
    }

    /**
     * @var Collection<int, Questlog>
     */
    #[ORM\ManyToMany(targetEntity: Questlog::class, mappedBy: 'favorites', cascade: ['remove'])]
    private $questlog_favorites;

    /**
     * @var Collection<int, Questlog>
     */
    #[ORM\ManyToMany(targetEntity: Questlog::class, mappedBy: 'votes', cascade: ['remove'])]
    private $questlog_votes;

    /**
     * Add questlogFavorite.
     */
    public function addQuestlogFavorite(Questlog $questlogFavorite): User
    {
        $this->questlog_favorites[] = $questlogFavorite;

        return $this;
    }

    /**
     * Remove questlogFavorite.
     */
    public function removeQuestlogFavorite(Questlog $questlogFavorite): void
    {
        $this->questlog_favorites->removeElement($questlogFavorite);
    }

    /**
     * Get questlogFavorites.
     *
     * @return Collection<int, Questlog>
     */
    public function getQuestlogFavorites(): Collection
    {
        return $this->questlog_favorites;
    }

    /**
     * Add questlogVote.
     */
    public function addQuestlogVote(Questlog $questlogVote): User
    {
        $this->questlog_votes[] = $questlogVote;

        return $this;
    }

    /**
     * Remove questlogVote.
     */
    public function removeQuestlogVote(Questlog $questlogVote): void
    {
        $this->questlog_votes->removeElement($questlogVote);
    }

    /**
     * Get questlogVotes.
     *
     * @return Collection<int, Questlog>
     */
    public function getQuestlogVotes(): Collection
    {
        return $this->questlog_votes;
    }

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    protected $locked = false;

    /**
     * Set locked.
     *
     * @param bool $locked
     */
    public function setLocked($locked): User
    {
        $this->locked = (bool) $locked;

        return $this;
    }

    /**
     * Get locked.
     */
    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean')]
    protected $expired = false;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'expires_at', type: 'datetime', nullable: true)]
    protected $expiresAt;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'credentials_expired', type: 'boolean')]
    protected $credentialsExpired = false;

    /**
     * @var \DateTime|null
     */
    #[ORM\Column(name: 'credentials_expire_at', type: 'datetime', nullable: true)]
    protected $credentialsExpireAt;

    /**
     * False when blocked by the admin ("Block" button). Checked by App\Security\UserChecker, as
     * the expiry checks below.
     */
    public function isAccountNonLocked(): bool
    {
        return !$this->locked;
    }

    public function isAccountNonExpired(): bool
    {
        if (true === $this->expired) {
            return false;
        }

        if (null !== $this->expiresAt && $this->expiresAt->getTimestamp() < time()) {
            return false;
        }

        return true;
    }

    public function isCredentialsNonExpired(): bool
    {
        if (true === $this->credentialsExpired) {
            return false;
        }

        if (null !== $this->credentialsExpireAt && $this->credentialsExpireAt->getTimestamp() < time()) {
            return false;
        }

        return true;
    }
}
