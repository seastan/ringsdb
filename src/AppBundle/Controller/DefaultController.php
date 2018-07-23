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
        $num_new_fellowships = 3;
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
        $num_comments = 8;

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
        // Limit number to $num_comments
        $all_comments = array_slice($all_comments,0,$num_comments);
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
            'all_comments' => $all_comments
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
