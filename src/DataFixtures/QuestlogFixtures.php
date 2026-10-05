<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Decklist;
use App\Entity\Questlog;
use App\Entity\QuestlogDeck;
use App\Entity\Scenario;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class QuestlogFixtures extends Fixture implements DependentFixtureInterface
{
    /**
     * @return array<int, class-string>
     */
    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            DecklistFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference('test-user');

        $questlog = new Questlog();
        $questlog->setSuccess(true);
        $questlog->setNbVotes(0);
        $questlog->setNbComments(0);
        $questlog->setNbFavorites(0);
        $questlog->setNbDecks(4);
        $questlog->setIsPublic(true);
        $questlog->setDatePublish(new \DateTime('2015-08-16'));

        $scenario = $manager->getRepository(Scenario::class)->find(1);
        if (!$scenario instanceof Scenario) {
            throw new \LogicException('Scenario 1 is missing.');
        }

        $questlog->setUser($user);
        $questlog->setName('Untitled Questlog');
        $questlog->setNameCanonical('untitled-questlog');
        $questlog->setDescriptionMd('Hello world');
        $questlog->setDescriptionHtml('Hello world');
        $questlog->setScenario($scenario);
        $questlog->setDatePlayed(new \DateTime('2015-08-16'));
        $questlog->setQuestMode('normal');
        $questlog->setSuccess(true);
        $questlog->setScore(25);
        $questlog->setDateCreation(new \DateTime('2015-08-16'));
        $questlog->setDateUpdate(new \DateTime('2015-08-16'));

        for ($i = 1; $i < 5; ++$i) {
            /** @var Decklist $decklist */
            $decklist = $this->getReference('test-decklist-'.$i);

            $questlog_decklist = new QuestlogDeck($questlog);
            $questlog_decklist->setDecklist($decklist);
            $questlog_decklist->setDeck($decklist->getParent());
            $questlog_decklist->setContent((string) json_encode($decklist->getContent()));
            $questlog_decklist->setDeckNumber($i);
            $questlog_decklist->setPlayer('Player '.$i);

            $questlog->addDeck($questlog_decklist);
        }

        $manager->persist($questlog);
        $manager->flush();
    }
}
