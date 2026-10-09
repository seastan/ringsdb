<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CardPrinting;
use App\Entity\Comment;
use App\Entity\Decklist;
use App\Entity\Fellowship;
use App\Entity\FellowshipComment;
use App\Entity\Review;
use App\Entity\Reviewcomment;
use App\Repository\ScenarioRepository;
use App\Repository\TypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class IndexController extends AbstractController
{
    public function __construct(
        private readonly int $cacheExpiration,
        private readonly ?string $gameName,
        private readonly ?string $publisherName,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/', name: 'index', methods: ['GET'])]
    public function __invoke(ScenarioRepository $scenarioRepository, TypeRepository $typeRepository): Response
    {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->cacheExpiration);

        $typeNames = [];
        foreach ($typeRepository->findAll() as $type) {
            $typeNames[$type->getCode()] = $type->getName();
        }

        // Daily Challenge
        $timesec = time();
        // Curent time in seconds
        $timebiday = intdiv($timesec, 24 * 60 * 60);
        // This value will increase by 1 every day
        mt_srand($timebiday);
        $quests = $scenarioRepository->findBy([], ['position' => 'ASC']);
        $randquest = $quests[array_rand($quests)];
        $challenges = ['using a Scout deck with no non-Scout characters', 'and reduce your threat by 10 more with a single South Away!', 'and kill at least 2 enemies with Hail of Stones', 'and kill at least 2 enemies at once with Rain of Arrows', 'and discard at least 2 enemies with Helm! Helm!', 'using a Dúnedain deck with no non-Dúnedain characters', 'using a Harad deck with no non-Harad allies', 'using a Trap deck with 3 copies of Interrogation', 'using a deck with Fastred', 'using a deck with Rossiel', 'using a deck with Elladan and Elrohir', "using a deck with Na'asiyah", 'using a deck with Tom Cotton', 'using a deck with hero Quickbeam', 'using a deck with Spirit Pippin', 'using a deck where every card costs 2', 'using a deck where every card costs 3', 'using the first deck you ever published', 'kill at least 2 full-health enemies with Dour-handed', 'using a deck where every hero has 1 printed willpower', 'using a deck with only allies', 'using a deck where every hero has 3 printed attack', 'using a deck with hero Elfhelm and a minimum of 15 Mount cards', 'using a deck that features the Palantir', 'using a Rohan deck where We Do Not Sleep, Forth Eorlingas!, and Charge of the Rohirrim are considered to have 0 cost, but if you do not play at least one of these three cards every round, you lose.', 'and heal at least 20 damage with a single Waters of Nimrodel', 'and kill at least 2 enemies with 1 Skyward Volley', 'using a deck where Trained for War and Ride them Down are both considered to have 0 cost, but only when played immediately one after the other.', 'and kill at least 2 enemies with Last Stand', 'and play Houses of Healing after reducing its cost to 0 at least once', 'and draw at least 6 cards with a single Old Toby', 'and play The Free Peoples at least once before the 5th round'];
        $randchallenge = $challenges[array_rand($challenges)];
        $daily_challenge = 'Daily Challenge: Play '.$randquest->getName().' '.$randchallenge.'.';
        // Trending Decks
        $num_trending = 3;
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('d');
        $qb->from(Decklist::class, 'd');
        $qb->setMaxResults($num_trending);
        $qb->distinct();
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
        $qb->andWhere($qb->expr()->gt($qb->expr()->length('d.descriptionHtml'), 0));
        $qb->orderBy('popularity', \SortDirection::Descending);
        $qb->addOrderBy('d.id', \SortDirection::Descending);

        $paginator = new Paginator($qb->getQuery(), $fetchJoinCollection = false);
        $decklists_trending = iterator_to_array($paginator->getIterator());
        // Trending Fellowships
        $num_trending_fellowships = 1;
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('d');
        $qb->from(Fellowship::class, 'd');
        $qb->setMaxResults($num_trending_fellowships);
        $qb->distinct();
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
        $qb->andWhere($qb->expr()->gt($qb->expr()->length('d.descriptionHtml'), 0));
        $qb->andWhere('d.isPublic = TRUE');
        $qb->orderBy('popularity', \SortDirection::Descending);
        $qb->addOrderBy('d.id', \SortDirection::Descending);

        $paginator = new Paginator($qb->getQuery(), $fetchJoinCollection = false);
        $fellowships_trending = iterator_to_array($paginator->getIterator());
        // New Decks
        $num_new = 3;
        // We want to be able to skip new decks that are trending,
        // so we grab $num_new+$num_trending recent decklists
        $num_trending = 3;
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('d');
        $qb->from(Decklist::class, 'd');
        $qb->setMaxResults($num_new + $num_trending);
        $qb->distinct();
        $qb->andWhere($qb->expr()->gt($qb->expr()->length('d.descriptionHtml'), 0));
        $qb->orderBy('d.dateCreation', \SortDirection::Descending);
        $qb->addOrderBy('d.id', \SortDirection::Descending);

        $paginator = new Paginator($qb->getQuery(), $fetchJoinCollection = false);
        $decklists_new_temp = iterator_to_array($paginator->getIterator());
        $decklists_new = [];
        for ($i = 0; $i < min($num_new + $num_trending, count($decklists_new_temp)); ++$i) {
            $decklist = $decklists_new_temp[$i];
            // Skip the decklist if it's trending
            if (in_array($decklist, $decklists_trending)) {
                continue;
            }

            // Limit number of entries to $num_new
            if (count($decklists_new) >= $num_new) {
                break;
            }

            $decklists_new[] = $decklist;
        }

        // New Fellowships
        $num_new_fellowships = 1;
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('d');
        $qb->from(Fellowship::class, 'd');
        $qb->setMaxResults($num_new_fellowships + $num_trending_fellowships);
        $qb->distinct();
        $qb->andWhere($qb->expr()->gt($qb->expr()->length('d.descriptionHtml'), 0));
        $qb->andWhere('d.isPublic = TRUE');
        $qb->orderBy('d.dateCreation', \SortDirection::Descending);
        $qb->addOrderBy('d.id', \SortDirection::Descending);

        $paginator = new Paginator($qb->getQuery(), $fetchJoinCollection = false);
        $fellowships_new_temp = iterator_to_array($paginator->getIterator());
        $fellowships_new = [];
        for ($i = 0; $i < min($num_new_fellowships + $num_trending_fellowships, count($fellowships_new_temp)); ++$i) {
            $fellowship = $fellowships_new_temp[$i];
            // Skip the fellowship if it's trending
            if (in_array($fellowship, $fellowships_trending)) {
                continue;
            }

            // Limit number of entries to $num_new_fellowships
            if (count($fellowships_new) >= $num_new_fellowships) {
                break;
            }

            $fellowships_new[] = $fellowship;
        }

        $this->loadDisplayedDecks(array_merge($decklists_trending, $decklists_new), array_merge($fellowships_trending, $fellowships_new));

        // Recent comments: the most recent decklist comments, fellowship comments, reviews and
        // review comments, merged and sorted by date
        $num_comments_displayed = 8;
        $all_comments = [];
        // A review is shown only if its card has a released printing
        $released_card = 'EXISTS (SELECT cp.id FROM '.CardPrinting::class.' cp JOIN cp.pack p WHERE cp.card = card AND p.dateRelease IS NOT NULL)';

        // Recent decklist comments
        $dql = 'SELECT c, u, d FROM '.Comment::class.' c JOIN c.user u JOIN c.decklist d WHERE c.isHidden = false ORDER BY c.dateCreation DESC, c.id DESC';
        foreach ($this->entityManager->createQuery($dql)->setMaxResults($num_comments_displayed)->getResult() as $comment) {
            $all_comments[] = [
                'type' => 'decklist',
                'decklist' => $comment->getDecklist(),
                'user' => $comment->getUser(),
                'dateCreation' => $comment->getDateCreation(),
                'text' => $comment->getText(),
            ];
        }

        // Recent fellowship comments
        $dql = 'SELECT c, u, f FROM '.FellowshipComment::class.' c JOIN c.user u JOIN c.fellowship f WHERE f.isPublic = true AND c.isHidden = false ORDER BY c.dateCreation DESC, c.id DESC';
        foreach ($this->entityManager->createQuery($dql)->setMaxResults($num_comments_displayed)->getResult() as $comment) {
            $all_comments[] = [
                'type' => 'fellowship',
                'fellowship' => $comment->getFellowship(),
                'user' => $comment->getUser(),
                'dateCreation' => $comment->getDateCreation(),
                'text' => $comment->getText(),
            ];
        }

        // Recent card reviews
        $dql = 'SELECT r, u, card FROM '.Review::class.' r JOIN r.user u JOIN r.card card WHERE '.$released_card.' ORDER BY r.dateCreation DESC, r.id DESC';
        foreach ($this->entityManager->createQuery($dql)->setMaxResults($num_comments_displayed)->getResult() as $review) {
            $all_comments[] = [
                'type' => 'review',
                'review' => $review,
                'user' => $review->getUser(),
                'dateCreation' => $review->getDateCreation(),
                'text' => $review->getTextHtml(),
            ];
        }

        // Recent review comments
        $dql = 'SELECT c, u, r, card FROM '.Reviewcomment::class.' c JOIN c.user u JOIN c.review r JOIN r.card card WHERE '.$released_card.' ORDER BY c.dateCreation DESC, c.id DESC';
        foreach ($this->entityManager->createQuery($dql)->setMaxResults($num_comments_displayed)->getResult() as $comment) {
            $all_comments[] = [
                'type' => 'reviewcomment',
                'review' => $comment->getReview(),
                'user' => $comment->getUser(),
                'dateCreation' => $comment->getDateCreation(),
                'text' => $comment->getText(),
            ];
        }

        // Sort all comments by date
        usort($all_comments, $this->orderNew(...));
        // Limit number to $num_comments_displayed
        $all_comments = array_slice($all_comments, 0, $num_comments_displayed);
        // Limit number of words in a comment
        for ($i = 0; $i < count($all_comments); ++$i) {
            $comment = $all_comments[$i];
            $text = $comment['text'];
            if (is_string($text) && strlen($text) > 300) {
                $text = (string) preg_replace('/\\s+?(\\S+)?$/', '', substr($text.' ', 0, 301));
                $strrpos = strrpos($text, '<');
                if (false !== $strrpos && $strrpos > strrpos($text, '>')) {
                    $text = substr($text.' ', 0, $strrpos);
                }

                $text = (string) preg_replace('/\\s+?(\\S+)?$/', '', $text);
                $text .= '...';
                // Fix unclosed html tags
                libxml_use_internal_errors(true);
                $dom = new \DOMDocument();
                $dom->loadHTML($text);
                // Strip wrapping <html> and <body> tags
                $mock = new \DOMDocument();
                $body = $dom->getElementsByTagName('body')->item(0);
                if ($body) {
                    foreach ($body->childNodes as $child) {
                        $mock->appendChild($mock->importNode($child, true));
                    }
                }

                $text = trim((string) $mock->saveHTML());
                $text = preg_replace('/\\n$/', '', $text);
            }

            $all_comments[$i]['text'] = $text;
        }

        $game_name = $this->gameName;
        $publisher_name = $this->publisherName;

        return $this->render('Default/index.html.twig', [
            'pagetitle' => "{$game_name} Deckbuilder",
            'pagedescription' => "Build your deck for {$game_name} by {$publisher_name}. Browse the cards and the thousand of decklists submitted by the community. Publish your own decks and get feedback.",
            'decklists_trending' => $decklists_trending,
            'fellowships_trending' => $fellowships_trending,
            'decklists_new' => $decklists_new,
            'fellowships_new' => $fellowships_new,
            'all_comments' => $all_comments,
            'daily_challenge' => $daily_challenge,
        ], $response);
    }

    /**
     * Loads the decklists of the fellowships, then the slots, cards and spheres of every displayed
     * decklist, so that getHeroDeck() runs without a query.
     *
     * The slots are not filtered on the hero type: Doctrine would mark the partial collections as
     * initialized.
     *
     * @param Decklist[]   $decklists
     * @param Fellowship[] $fellowships
     */
    private function loadDisplayedDecks(array $decklists, array $fellowships): void
    {
        if ([] !== $fellowships) {
            $this->entityManager
                ->createQuery('SELECT f, fd, d FROM '.Fellowship::class.' f LEFT JOIN f.decklists fd LEFT JOIN fd.decklist d WHERE f IN (:fellowships) ORDER BY fd.id')
                ->setParameter('fellowships', $fellowships)
                ->getResult();
            foreach ($fellowships as $fellowship) {
                foreach ($fellowship->getDecklists() as $fellowshipDecklist) {
                    $decklists[] = $fellowshipDecklist->getDecklist();
                }
            }
        }

        if ([] === $decklists) {
            return;
        }

        $this->entityManager
            ->createQuery('SELECT d, s, c, sp FROM '.Decklist::class.' d LEFT JOIN d.slots s LEFT JOIN s.card c LEFT JOIN c.sphere sp WHERE d.id IN (:ids)')
            ->setParameter('ids', array_unique(array_map(static fn (Decklist $decklist): ?int => $decklist->getId(), $decklists)))
            ->getResult();
    }

    /**
     * Newest first.
     *
     * @param array<string, mixed> $a
     * @param array<string, mixed> $b
     */
    private function orderNew(array $a, array $b): int
    {
        return $b['dateCreation'] <=> $a['dateCreation'];
    }
}
