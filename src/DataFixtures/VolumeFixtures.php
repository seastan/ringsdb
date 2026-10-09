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
use App\Entity\Review;
use App\Entity\Reviewcomment;
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
 * - 60 copies of the 4 test decklists, with a description and 3 comments each;
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
     * Copies of the test decklists, published one day apart, each followed by its comments. The
     * last comment of every 10th decklist is hidden.
     *
     * @param list<User> $users
     *
     * @return list<Decklist>
     */
    private function createDecklists(ObjectManager $manager, array $users): array
    {
        $decklists = [];
        for ($i = 0; $i < self::DECKLISTS; ++$i) {
            /** @var Decklist $source */
            $source = $this->getReference('test-decklist-'.($i % 4 + 1), Decklist::class);
            $date = self::date($i * 24);

            $decklist = $this->copyDecklist($source, $users[$i % self::USERS], ucfirst(self::lorem($i, 1, 4)).' '.($i + 1), $date);
            $decklist->setNbVotes($i * 7 % 11);

            for ($j = 1; $j <= self::DECKLIST_COMMENTS; ++$j) {
                $comment = new Comment($users[($i + $j) % self::USERS], $decklist, $this->texts->markdown(self::lorem($i + $j, 1 + $j)));
                $comment->setDateCreation(self::date($i * 24 + $j));
                $comment->setIsHidden(0 === $i % 10 && self::DECKLIST_COMMENTS === $j);
                $decklist->getComments()->add($comment);
                $manager->persist($comment);
            }
            $decklist->setNbcomments(self::DECKLIST_COMMENTS);
            $decklist->setDateLastComment(self::date($i * 24 + self::DECKLIST_COMMENTS));

            $manager->persist($decklist);
            $decklists[] = $decklist;
        }

        return $decklists;
    }

    private function copyDecklist(Decklist $source, User $user, string $name, \DateTime $date): Decklist
    {
        $descriptionMd = self::lorem($source->getStartingThreat(), 6)."\n\n".self::lorem($source->getStartingThreat() + 3, 4);

        $decklist = new Decklist($user);
        $decklist->setName($name);
        $decklist->setVersion($source->getVersion());
        $decklist->setNameCanonical($this->texts->slugify($name).'-'.$source->getVersion());
        $decklist->setDescriptionMd($descriptionMd);
        $decklist->setDescriptionHtml($this->texts->markdown($descriptionMd));
        // not the signature of the source: the copies are not published versions of its deck
        $decklist->setSignature(md5($name));
        $decklist->setLastPack($source->getLastPack());
        $decklist->setPredominantSphere($source->getPredominantSphere());
        $decklist->setStartingThreat($source->getStartingThreat());
        $decklist->setDateCreation($date);
        $decklist->setDateUpdate($date);

        foreach ($source->getSlots() as $slot) {
            $decklist->getSlots()->add(new Decklistslot($decklist, $slot->getCard(), $slot->getQuantity()));
        }
        foreach ($source->getSideslots() as $slot) {
            $decklist->getSideslots()->add(new Decklistsideslot($decklist, $slot->getCard(), $slot->getQuantity()));
        }
        foreach ($source->getSpheres() as $sphere) {
            $decklist->addSphere($sphere);
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
            $date = self::date($i * 24 + 12);
            $name = ucfirst(self::lorem($i + 2, 1, 5)).' '.($i + 1);
            $descriptionMd = self::lorem($i, 5);

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
                $comment = new FellowshipComment($users[($i + $j) % self::USERS], $fellowship, $this->texts->markdown(self::lorem($i + 2 * $j, 2)));
                $comment->setDateCreation(self::date($i * 24 + 12 + $j));
                $comment->setDateUpdate(self::date($i * 24 + 12 + $j));
                $fellowship->addComment($comment);
            }
            $fellowship->setNbComments(self::FELLOWSHIP_COMMENTS);
            $fellowship->setDateLastComment(self::date($i * 24 + 12 + self::FELLOWSHIP_COMMENTS));

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
            $date = self::date($i * 24 + 6);
            $textMd = self::lorem($i + 1, 4)."\n\n".self::lorem($i + 5, 3);

            $review = new Review($users[($i + 5) % self::USERS], $card, $textMd, $this->texts->markdown($textMd));
            $review->setNbVotes($i * 3 % 5);
            $review->setDateCreation($date);
            $review->setDateUpdate($date);

            for ($j = 1; $j <= self::REVIEW_COMMENTS; ++$j) {
                $comment = new Reviewcomment($users[($i + 5 + $j) % self::USERS], $review, self::lorem($i + 3 * $j, 2));
                $comment->setDateCreation(self::date($i * 24 + 6 + $j));
                $comment->setDateUpdate(self::date($i * 24 + 6 + $j));
                $review->addComment($comment);
                $manager->persist($comment);
            }
            $review->setDateLastComment(self::date($i * 24 + 6 + self::REVIEW_COMMENTS));

            $manager->persist($review);
        }
    }

    /**
     * 2014-01-01 plus a number of hours.
     */
    private static function date(int $hours): \DateTime
    {
        return new \DateTime(sprintf('2014-01-01 00:00:00 +%d hours', $hours));
    }

    /**
     * $count lorem ipsum sentences from the $offset-th one, or their first $words words.
     */
    private static function lorem(int $offset, int $count, ?int $words = null): string
    {
        $sentences = [];
        for ($i = 0; $i < $count; ++$i) {
            $sentences[] = self::LOREM[($offset + $i) % count(self::LOREM)];
        }
        $text = implode(' ', $sentences);

        return null === $words ? $text : rtrim(implode(' ', array_slice(explode(' ', $text), 0, $words)), ',.');
    }
}
