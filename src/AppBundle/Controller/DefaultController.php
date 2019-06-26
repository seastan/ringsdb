<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Model\DecklistManager;
use AppBundle\Model\FellowshipManager;
use AppBundle\Entity\Decklist;
use Doctrine\ORM\Query;

use Doctrine\ORM\Tools\Pagination\Paginator;



class DefaultController extends Controller {
    function orderNew($a, $b) {
        return ($a['dateCreation'] < $b['dateCreation']);
    }

    public function indexAction() {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        // Managers
        $decklist_manager = $this->get('decklist_manager');
        $fellowship_manager = $this->get('fellowship_manager');
        $em = $this->getDoctrine()->getManager();
        
        $typeNames = [];
        foreach($this->getDoctrine()->getRepository('AppBundle:Type')->findAll() as $type) {
        	$typeNames[$type->getCode()] = $type->getName();
        }

	// Daily Challenge
        $timesec = time(); // Curent time in seconds
        $timebiday = intdiv($timesec, 24*60*60); // This value will increase by 1 every day
        srand($timebiday);
        $quests = $em->getRepository('AppBundle:Scenario')->findBy([], ['position' => 'ASC']);
	$numquests = count($quests);
	$randquest = $quests[array_rand($quests)];
        $challenges = array('using a Scout deck with no non-Scout characters',
                            'and reduce your threat by 10 more with a single South Away!',
                            'and kill at least 2 enemies with Hail of Stones',
                            'and kill at least 2 enemies at once with Rain of Arrows',
                            'and discard at least 2 enemies with Helm! Helm!',
                            'using a Dúnedain deck with no non-Dúnedain characters',
                            'using a Harad deck with no non-Harad allies',
                            'using a Trap deck with 3 copies of Interrogation',
                            'using a deck with Fastred',
                            'using a deck with Rossiel',
                            'using a deck with Elladan and Elrohir',
                            'using a deck with Na\'asiyah',
                            'using a deck with Tom Cotton',
                            'using a deck with hero Quickbeam',
                            'using a deck with Spirit Pippin',
                            'using a deck where every card costs 2',
                            'using a deck where every card costs 3',
                            'using the first deck you ever published',
                            'kill at least 2 full-health enemies with Dour-handed',
                            'using a deck where every hero has 1 printed willpower',
                            'using a deck with only allies',
                            'using a deck where every hero has 3 printed attack',
                            'using a deck with hero Elfhelm and a minimum of 15 Mount cards',
                            'using a deck that features the Palantir',
                            'using a Rohan deck where We Do Not Sleep, Forth Eorlingas!, and Charge of the Rohirrim are considered to have 0 cost, but if you do not play at least one of these three cards every round, you lose.',
                            'and heal at least 20 damage with a single Waters of Nimrodel',
                            'and kill at least 2 enemies with 1 Skyward Volley',
                            'using a deck where Trained for War and Ride them Down are both considered to have 0 cost, but only when played immediately one after the other.',
                            'and kill at least 2 enemies with Last Stand',
                            'and play Houses of Healing after reducing its cost to 0 at least once',
                            'and draw at least 6 cards with a single Old Toby',
                            'and play The Free Peoples at least once before the 5th round'
                             );
	$randchallenge = $challenges[array_rand($challenges)];
        $daily_challenge = 'Daily Challenge: Play ' . $randquest->getName() . ' ' . $randchallenge . '.';

        // Trending Decks
        $num_trending = 3;
        $qb = $em->createQueryBuilder();
		$qb->select('d');
		$qb->from('AppBundle:Decklist', 'd');
		$qb->setMaxResults($num_trending);
		$qb->distinct();
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
        $qb->andWhere($qb->expr()->gt($qb->expr()->length('d.descriptionHtml'),0));
        $qb->orderBy('popularity', 'DESC');
        $paginator = new Paginator($qb->getQuery(), $fetchJoinCollection = false);
        $decklists_trending = iterator_to_array($paginator->getIterator());

        // Trending Fellowships
        $num_trending_fellowships = 1;
        $qb = $em->createQueryBuilder();
		$qb->select('d');
		$qb->from('AppBundle:Fellowship', 'd');
		$qb->setMaxResults($num_trending_fellowships);
		$qb->distinct();
        $qb->addSelect('(1+d.nbVotes)/(1+POWER(DATE_DIFF(CURRENT_TIMESTAMP(), d.dateCreation), 2)) AS HIDDEN popularity');
        $qb->andWhere($qb->expr()->gt($qb->expr()->length('d.descriptionHtml'),0));
        $qb->andWhere('d.isPublic = TRUE');
        $qb->orderBy('popularity', 'DESC');
        $paginator = new Paginator($qb->getQuery(), $fetchJoinCollection = false);
        $fellowships_trending = iterator_to_array($paginator->getIterator());


        // New Decks
        $num_new = 3;
        // We want to be able to skip new decks that are trending, 
        // so we grab $num_new+$num_trending recent decklists
        $num_trending = 3;
        $qb = $em->createQueryBuilder();
		$qb->select('d');
		$qb->from('AppBundle:Decklist', 'd');
		$qb->setMaxResults($num_new+$num_trending);
		$qb->distinct();
        $qb->andWhere($qb->expr()->gt($qb->expr()->length('d.descriptionHtml'),0));
        $qb->orderBy('d.dateCreation', 'DESC');
        $paginator = new Paginator($qb->getQuery(), $fetchJoinCollection = false);
        $decklists_new_temp = iterator_to_array($paginator->getIterator());
        $decklists_new = [];
        for ($i = 0; $i < min($num_new+$num_trending,count($decklists_new_temp)); $i++) {
            $decklist = $decklists_new_temp[$i];
            // Skip the decklist if it's trending
            if (in_array($decklist,$decklists_trending)) continue;
            // Limit number of entries to $num_new
            if (count($decklists_new)>=$num_new) break;
            $decklists_new[] = $decklist;
        }

        // New Fellowships
        $num_new_fellowships = 1;
        $qb = $em->createQueryBuilder();
		$qb->select('d');
		$qb->from('AppBundle:Fellowship', 'd');
		$qb->setMaxResults($num_new_fellowships+$num_trending_fellowships);
		$qb->distinct();
        $qb->andWhere($qb->expr()->gt($qb->expr()->length('d.descriptionHtml'),0));
        $qb->andWhere('d.isPublic = TRUE');
        $qb->orderBy('d.dateCreation', 'DESC');
        $paginator = new Paginator($qb->getQuery(), $fetchJoinCollection = false);
        $fellowships_new_temp = iterator_to_array($paginator->getIterator());
        $fellowships_new = [];
        for ($i = 0; $i < min($num_new_fellowships+$num_trending_fellowships,count($fellowships_new_temp)); $i++) {
            $fellowship = $fellowships_new_temp[$i];
            // Skip the fellowship if it's trending
            if (in_array($fellowship,$fellowships_trending)) continue;
            // Limit number of entries to $num_new_fellowships
            if (count($fellowships_new)>=$num_new_fellowships) break;
            $fellowships_new[] = $fellowship;
        }        

        // This will contain all the comments - a combination of decklist comments, fellowship comments, 
        // reviews, review comments, etc.
        $all_comments = [];

        // Number of recent comments to show on the front page
        // BUG: It seems like the setDateLastComment I added to Decklists, Fellowships, and Reviews somehow
        // gets called spontaneuosly on decks without recent comments, even though the only place 
        // setDateLastComment appears is in the commentAction in the SocialController, FellowshipController,
        // and ReviewController. This results in decks with no recent comments being returned by
        // find___ByRecentDiscussion(). When these get compared to the dateCreation of recent Reviews,
        // which works properly, naturally the reviews win out, and the resulting top $num_comments end up
        // being mostly reviews. One current workaround I have is to make $num_comments very large so that even
        // with some old decklists finding there way in, there will be enough deckslists with real recent
        // comments that it wont matter. Then, after sorting $all_comments by date, we trim down to the
        // number we actually want.
        $num_comments = 50;

        // Recent decklist comments
        $decklist_manager->setLimit($num_comments);
        $paginator = $decklist_manager->findDecklistsByRecentDiscussion();
        $decklists_recent_discussion = iterator_to_array($paginator->getIterator());
        for ($i = 0; $i < min($num_comments,count($decklists_recent_discussion)); $i++) {
            $decklist = $decklists_recent_discussion[$i];
            $comment = [];
            if ($decklist) {
                $lastcomment = $decklist->getComments()->last();
                if ($lastcomment) {
                    if ($lastcomment->getIsHidden()) {
                        continue;
                    }
                    $comment['type'] = 'decklist';
                    $comment['decklist'] = $decklist;
                    $comment['user'] = $lastcomment->getUser();
                    $comment['dateCreation'] = $lastcomment->getDateCreation();
                    $comment['text'] = $lastcomment->getText();
                } else {
                    continue;
                }
            }
            $all_comments[] = $comment;
        }
        // Recent fellowship comments
        $fellowship_manager->setLimit($num_comments);
        $paginator = $fellowship_manager->findFellowshipsByRecentDiscussion();
        $fellowships_recent_discussion = iterator_to_array($paginator->getIterator());
        for ($i = 0; $i < min($num_comments,count($fellowships_recent_discussion)); $i++) {
            $fellowship = $fellowships_recent_discussion[$i];
            $comment = [];
            if ($fellowship) {
                $lastcomment = $fellowship->getComments()->last();
                if ($lastcomment) {
                    $comment['type'] = 'fellowship';
                    $comment['fellowship'] = $fellowship;
                    $comment['user'] = $lastcomment->getUser();
                    $comment['dateCreation'] = $lastcomment->getDateCreation();
                    $comment['text'] = $lastcomment->getText();
                } else {
                    continue;
                }
            }
            $all_comments[] = $comment;
        }
        // Get recent card reviews
        $dql = "SELECT r FROM AppBundle:Review r JOIN r.card c JOIN c.pack p WHERE p.dateRelease IS NOT NULL ORDER BY r.dateCreation DESC";
        $query = $em->createQuery($dql)->setMaxResults($num_comments);
        $paginator = new Paginator($query, false);
        $reviews_recent = iterator_to_array($paginator->getIterator());
        for ($i = 0; $i < min($num_comments,count($reviews_recent)); $i++) {
            $review = $reviews_recent[$i];
            $comment = [];
            if ($review) {
                $comment['type'] = 'review';
                $comment['review'] = $review;
                $comment['user'] = $review->getUser();
                $comment['dateCreation'] = $review->getDateCreation();
                $comment['text'] = $review->getTextHtml();
            }
            $all_comments[] = $comment;
        }
        // Recent review comments
        $em = $this->getDoctrine()->getManager();
        $dql = "SELECT r FROM AppBundle:Review r JOIN r.card c JOIN c.pack p WHERE p.dateRelease IS NOT NULL ORDER BY r.dateLastComment DESC";
        $query = $em->createQuery($dql)->setMaxResults($num_comments);
        $paginator = new Paginator($query, false);
        $reviews_recent_discussion = iterator_to_array($paginator->getIterator());
        for ($i = 0; $i < min($num_comments,count($reviews_recent_discussion)); $i++) {
            $review = $reviews_recent_discussion[$i];
            $comment = [];
            if ($review) {
                $lastcomment = $review->getComments()->last();
                if ($lastcomment) {
                    $comment['type'] = 'reviewcomment';
                    $comment['review'] = $review;
                    $comment['user'] = $lastcomment->getUser();
                    $comment['dateCreation'] = $lastcomment->getDateCreation();
                    $comment['text'] = $lastcomment->getText();
                } else {
                    continue;
                }
            }
            $all_comments[] = $comment;
        }

        // Sort all comments by date
        usort($all_comments, array($this, "orderNew"));
        $num_comments_displayed = 8;
        // Limit number to $num_comments
        $all_comments = array_slice($all_comments,0,$num_comments_displayed);
        // Limit number of words in a comment
        for ($i = 0; $i < count($all_comments); $i++) {
            $comment = $all_comments[$i];
            $text = $comment['text'];
            if (strlen($text) > 300) {
                $text = preg_replace('/\s+?(\S+)?$/', '', substr($text . ' ', 0, 301));
                if (strrpos($text, '<') > strrpos($text, '>')) $text = substr($text . ' ', 0, strrpos($text, '<')); 
                $text = preg_replace('/\s+?(\S+)?$/', '', $text);
                $text = $text . '...';
                // Fix unclosed html tags
                libxml_use_internal_errors(true);
                $dom = new \DOMDocument;
                $dom->loadHTML($text);
                // Strip wrapping <html> and <body> tags
                $mock = new \DOMDocument;
                $body = $dom->getElementsByTagName('body')->item(0);
                foreach ($body->childNodes as $child) {
                    $mock->appendChild($mock->importNode($child, true));
                }
                $text = trim($mock->saveHTML());
                $text = preg_replace('/\n$/','',$text);
            }
            $all_comments[$i]['text'] = $text;
        }

        $game_name = $this->container->getParameter('game_name');
        $publisher_name = $this->container->getParameter('publisher_name');
        
        return $this->render('AppBundle:Default:index.html.twig', [
            'pagetitle' =>  "$game_name Deckbuilder",
            'pagedescription' => "Build your deck for $game_name by $publisher_name. Browse the cards and the thousand of decklists submitted by the community. Publish your own decks and get feedback.",
            'decklists_trending' => $decklists_trending,
            'fellowships_trending' => $fellowships_trending,
            'decklists_new' => $decklists_new,
            'fellowships_new' => $fellowships_new,
            'all_comments' => $all_comments,
            'daily_challenge' => $daily_challenge
        ], $response);
    }

    function rulesAction() {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        $render = $this->renderView('AppBundle:Default:rules.html.twig', [
            "pagetitle" => "Rules",
            "pagedescription" => "Refer to the official rules of the game."
        ]);

        $page = $this->get('cards_data')->replaceSymbols($render);
        $response->setContent($page);

        return $response;
    }

    function aboutAction() {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        return $this->render('AppBundle:Default:about.html.twig', [
            "pagetitle" => "About",
            "game_name" => $this->container->getParameter('game_name'),
        ], $response);
    }

    function apiIntroAction() {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        return $this->render('AppBundle:Default:apiIntro.html.twig', [
            "pagetitle" => "API",
            "game_name" => $this->container->getParameter('game_name'),
            "publisher_name" => $this->container->getParameter('publisher_name'),
        ], $response);
    }
}
