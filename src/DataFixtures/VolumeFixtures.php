<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Card;
use App\Entity\Comment;
use App\Entity\Decklist;
use App\Entity\Decklistsideslot;
use App\Entity\Decklistslot;
use App\Entity\Fellowship;
use App\Entity\FellowshipComment;
use App\Entity\FellowshipDecklist;
use App\Entity\Pack;
use App\Entity\Review;
use App\Entity\Reviewcomment;
use App\Entity\Sphere;
use App\Entity\User;
use App\Security\UserPasswordUpdater;
use App\Services\Texts;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

/**
 * A production-like volume of community content, so that the pages that list it (the home page
 * first, see docs/issues/index-optimization.md) run the same queries as in production:
 * - 10 users;
 * - 60 decklists with the contents of 60 different production decklists (volume-decklists.json:
 *   cards only), with a description and 3 comments each;
 * - 50 public fellowships of 4 of those decklists, with a description and 2 comments each;
 * - 50 card reviews, with 2 comments each.
 *
 * The texts are lorem ipsum. The dates are fixed, from 2014-01-01: before the other fixtures, which
 * stay first in the lists sorted by date.
 */
class VolumeFixtures extends Fixture implements DependentFixtureInterface
{
    private const int USERS = 10;

    private const int DECKLISTS = 60;

    private const int DECKLIST_COMMENTS = 3;

    private const int FELLOWSHIPS = 50;

    private const int FELLOWSHIP_DECKS = 4;

    private const int FELLOWSHIP_COMMENTS = 2;

    private const int REVIEWS = 50;

    private const int REVIEW_COMMENTS = 2;

    private const array LOREM = [
        'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
        'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
        'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
        'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
        'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
        'Curabitur pretium tincidunt lacus, nulla gravida orci a odio.',
        'Nullam varius, turpis et commodo pharetra, est eros bibendum elit, nec luctus magna felis sollicitudin mauris.',
        'Integer in mauris eu nibh euismod gravida.',
    ];

    public function __construct(
        private readonly UserPasswordUpdater $passwordUpdater,
        private readonly Texts $texts,
    ) {
    }

    /**
     * @return array<int, class-string>
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            DecklistFixtures::class,
            CommentFixtures::class,
            FellowshipFixtures::class,
            ReviewFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $users = $this->createUsers($manager);
        $decklists = $this->createDecklists($manager, $users);
        $this->createFellowships($manager, $users, $decklists);
        $this->createReviews($manager, $users);

        $manager->flush();
    }

    /**
     * @return list<User>
     */
    private function createUsers(ObjectManager $manager): array
    {
        $users = [];
        for ($i = 1; $i <= self::USERS; ++$i) {
            $username = sprintf('member%02d', $i);
            $user = new User();
            $user->setUsername($username);
            $user->setEmail($username.'@example.com');
            $user->setPlainPassword($username);
            $user->setEnabled(true);
            $user->setDateCreation(new \DateTime('2013-12-01'));
            $user->setDateUpdate(new \DateTime('2013-12-01'));

            $this->passwordUpdater->hashPassword($user);
            $manager->persist($user);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Decklists with the contents of production decklists (volume-decklists.json), published
     * one day apart, each followed by its comments. The last comment of every 10th decklist is
     * hidden.
     *
     * @param list<User> $users
     *
     * @return list<Decklist>
     */
    private function createDecklists(ObjectManager $manager, array $users): array
    {
        /** @var list<array{source: string, last_pack: string, slots: array<string, int>, sideslots: array<string, int>}> $contents */
        $contents = json_decode((string) file_get_contents(__DIR__.'/volume-decklists.json'), true, flags: JSON_THROW_ON_ERROR);
        $cards = $this->findCards($manager, $contents);

        $decklists = [];
        for ($i = 0; $i < self::DECKLISTS; ++$i) {
            $date = $this->date($i * 24);

            $decklist = $this->createDecklist($manager, $contents[$i % count($contents)], $cards, $users[$i % self::USERS], ucfirst($this->lorem($i, 1, 4)).' '.($i + 1), $date);
            $decklist->setNbVotes($i * 7 % 11);

            for ($j = 1; $j <= self::DECKLIST_COMMENTS; ++$j) {
                $comment = new Comment($users[($i + $j) % self::USERS], $decklist, $this->texts->markdown($this->lorem($i + $j, 1 + $j)));
                $comment->setDateCreation($this->date($i * 24 + $j));
                $comment->setIsHidden(0 === $i % 10 && self::DECKLIST_COMMENTS === $j);
                $decklist->getComments()->add($comment);
                $manager->persist($comment);
            }

            $decklist->setNbcomments(self::DECKLIST_COMMENTS);
            $decklist->setDateLastComment($this->date($i * 24 + self::DECKLIST_COMMENTS));

            $manager->persist($decklist);
            $decklists[] = $decklist;
        }

        return $decklists;
    }

    /**
     * @param list<array{slots: array<string, int>, sideslots: array<string, int>}> $contents
     *
     * @return array<string, Card> by code
     */
    private function findCards(ObjectManager $manager, array $contents): array
    {
        $codes = [];
        foreach ($contents as $content) {
            $codes = array_merge($codes, array_map(strval(...), array_keys($content['slots'] + $content['sideslots'])));
        }

        $cards = [];
        foreach ($manager->getRepository(Card::class)->findBy(['code' => array_unique($codes)]) as $card) {
            $cards[$card->getCode()] = $card;
        }

        if ($missing = array_diff($codes, array_keys($cards))) {
            throw new \LogicException('Unknown cards: '.implode(', ', array_unique($missing)));
        }

        return $cards;
    }

    /**
     * The derived fields (spheres, predominant sphere, starting threat) are computed like
     * DecklistFactory does.
     *
     * @param array{source: string, last_pack: string, slots: array<string, int>, sideslots: array<string, int>} $content
     * @param array<string, Card>                                                                                $cards
     */
    private function createDecklist(ObjectManager $manager, array $content, array $cards, User $user, string $name, \DateTime $date): Decklist
    {
        $descriptionMd = $this->lorem(strlen($name), 6)."\n\n".$this->lorem(strlen($name) + 3, 4);

        $decklist = new Decklist($user);
        $decklist->setName($name);
        $decklist->setVersion('1.0');
        $decklist->setNameCanonical($this->texts->slugify($name).'-1.0');
        $decklist->setDescriptionMd($descriptionMd);
        $decklist->setDescriptionHtml($this->texts->markdown($descriptionMd));
        $decklist->setSignature(md5($name));
        $decklist->setLastPack($manager->getRepository(Pack::class)->findOneBy(['name' => $content['last_pack']]));
        $decklist->setDateCreation($date);
        $decklist->setDateUpdate($date);

        foreach ($content['slots'] as $code => $quantity) {
            $decklist->getSlots()->add(new Decklistslot($decklist, $cards[(string) $code], $quantity));
        }

        foreach ($content['sideslots'] as $code => $quantity) {
            $decklist->getSideslots()->add(new Decklistsideslot($decklist, $cards[(string) $code], $quantity));
        }

        $countBySphere = $decklist->getSlots()->getCountBySphere();
        $predominantSphere = array_keys($countBySphere, max(...array_values($countBySphere)))[0];
        $decklist->setPredominantSphere($manager->getRepository(Sphere::class)->findOneBy(['code' => $predominantSphere]));
        $decklist->setStartingThreat($decklist->getSlots()->getStartingThreat());
        foreach ($decklist->getSlots()->getHeroDeck() as $hero) {
            $sphere = $hero->getCard()->getSphere();
            if ($sphere instanceof Sphere && !$decklist->getSpheres()->contains($sphere)) {
                $decklist->addSphere($sphere);
            }
        }

        return $decklist;
    }

    /**
     * @param list<User>     $users
     * @param list<Decklist> $decklists
     */
    private function createFellowships(ObjectManager $manager, array $users, array $decklists): void
    {
        for ($i = 0; $i < self::FELLOWSHIPS; ++$i) {
            $date = $this->date($i * 24 + 12);
            $name = ucfirst($this->lorem($i + 2, 1, 5)).' '.($i + 1);
            $descriptionMd = $this->lorem($i, 5);

            $fellowship = new Fellowship($users[($i + 3) % self::USERS]);
            $fellowship->setIsPublic(true);
            $fellowship->setNbVotes($i * 5 % 7);
            $fellowship->setNbFavorites(0);
            $fellowship->setName($name);
            $fellowship->setNameCanonical($this->texts->slugify($name));
            $fellowship->setDescriptionMd($descriptionMd);
            $fellowship->setDescriptionHtml($this->texts->markdown($descriptionMd));
            $fellowship->setDateCreation($date);
            $fellowship->setDateUpdate($date);
            $fellowship->setDatePublish($date);

            for ($j = 0; $j < self::FELLOWSHIP_DECKS; ++$j) {
                $fellowshipDecklist = new FellowshipDecklist($decklists[($i * self::FELLOWSHIP_DECKS + $j) % self::DECKLISTS], $fellowship);
                $fellowshipDecklist->setDeckNumber($j + 1);
                $fellowship->addDecklist($fellowshipDecklist);
            }

            $fellowship->setNbDecks(self::FELLOWSHIP_DECKS);

            for ($j = 1; $j <= self::FELLOWSHIP_COMMENTS; ++$j) {
                $comment = new FellowshipComment($users[($i + $j) % self::USERS], $fellowship, $this->texts->markdown($this->lorem($i + 2 * $j, 2)));
                $comment->setDateCreation($this->date($i * 24 + 12 + $j));
                $comment->setDateUpdate($this->date($i * 24 + 12 + $j));
                $fellowship->addComment($comment);
            }

            $fellowship->setNbComments(self::FELLOWSHIP_COMMENTS);
            $fellowship->setDateLastComment($this->date($i * 24 + 12 + self::FELLOWSHIP_COMMENTS));

            $manager->persist($fellowship);
        }
    }

    /**
     * Reviews of the last cards of released packs, outside the Core Set that the tests use.
     *
     * @param list<User> $users
     */
    private function createReviews(ObjectManager $manager, array $users): void
    {
        /** @var list<Card> $cards */
        $cards = $manager->getRepository(Card::class)->createQueryBuilder('c')
            ->join('c.printings', 'cp')
            ->join('cp.pack', 'p')
            ->where('p.dateRelease IS NOT NULL')
            ->andWhere("c.code NOT LIKE '01%'")
            ->groupBy('c.id')
            ->orderBy('c.code', 'DESC')
            ->setMaxResults(self::REVIEWS)
            ->getQuery()
            ->getResult();

        foreach ($cards as $i => $card) {
            $date = $this->date($i * 24 + 6);
            $textMd = $this->lorem($i + 1, 4)."\n\n".$this->lorem($i + 5, 3);

            $review = new Review($users[($i + 5) % self::USERS], $card, $textMd, $this->texts->markdown($textMd));
            $review->setNbVotes($i * 3 % 5);
            $review->setDateCreation($date);
            $review->setDateUpdate($date);

            for ($j = 1; $j <= self::REVIEW_COMMENTS; ++$j) {
                $comment = new Reviewcomment($users[($i + 5 + $j) % self::USERS], $review, $this->lorem($i + 3 * $j, 2));
                $comment->setDateCreation($this->date($i * 24 + 6 + $j));
                $comment->setDateUpdate($this->date($i * 24 + 6 + $j));
                $review->addComment($comment);
                $manager->persist($comment);
            }

            $review->setDateLastComment($this->date($i * 24 + 6 + self::REVIEW_COMMENTS));

            $manager->persist($review);
        }
    }

    /**
     * 2014-01-01 plus a number of hours.
     */
    private function date(int $hours): \DateTime
    {
        return new \DateTime(sprintf('2014-01-01 00:00:00 +%d hours', $hours));
    }

    /**
     * $count lorem ipsum sentences from the $offset-th one, or their first $words words.
     */
    private function lorem(int $offset, int $count, ?int $words = null): string
    {
        $sentences = [];
        for ($i = 0; $i < $count; ++$i) {
            $sentences[] = self::LOREM[($offset + $i) % count(self::LOREM)];
        }

        $text = implode(' ', $sentences);

        return null === $words ? $text : rtrim(implode(' ', array_slice(explode(' ', $text), 0, $words)), ',.');
    }
}
