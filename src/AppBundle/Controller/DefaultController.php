<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use AppBundle\Model\DecklistManager;
use AppBundle\Entity\Decklist;

class DefaultController extends Controller {

    public function indexAction() {
        $response = new Response();
        $response->setPublic();
        $response->setMaxAge($this->container->getParameter('cache_expiration'));

        /** 
         * @var $decklist_manager DecklistManager  
         */
        $decklist_manager = $this->get('decklist_manager');
        $decklist_manager->setLimit(1);
        
        $typeNames = [];
        foreach($this->getDoctrine()->getRepository('AppBundle:Type')->findAll() as $type) {
        	$typeNames[$type->getCode()] = $type->getName();
        }
        
        $decklists_by_sphere = [];
        $spheres = $this->getDoctrine()->getRepository('AppBundle:Sphere')->findBy(['is_primary' => true], ['code' => 'ASC']);
        
        foreach($spheres as $sphere) {
            $array = [];
            $array['sphere'] = $sphere;

        	$decklist_manager->setPredominantSphere($sphere);
        	$paginator = $decklist_manager->findDecklistsByPopularity();
        	/**
        	 * @var $decklist Decklist
        	 */
            $decklist = $paginator->getIterator()->current();

            if ($decklist) {
                $array['decklist'] = $decklist;

                $heroDeck = $decklist->getSlots()->getHeroDeck();
                $array['hero_deck'] = $heroDeck;

                $countByType = $decklist->getSlots()->getCountByType();
                $counts = [];
                foreach ($countByType as $code => $qty) {
                    if ($code == 'hero' || $qty == 0) {
                        continue;
                    }
                    $typeName = $typeNames[$code];
                    $counts[] = $qty . " " . ($typeName == 'Ally' ? 'Allie' : $typeName) . "s";
                }
                $array['count_by_type'] = join(' &bull; ', $counts);

                $array['starting_threat'] = $decklist->getSlots()->getStartingThreat();

                $spheres = [];
                foreach ($heroDeck as $h) {
                    $spheres[] = $h->getCard()->getSphere()->getName();
                }
                $array['sphere_names'] = join(' / ', array_unique($spheres));

                $decklists_by_sphere[] = $array;
            }
        }

        $game_name = $this->container->getParameter('game_name');
        $publisher_name = $this->container->getParameter('publisher_name');
        
        return $this->render('AppBundle:Default:index.html.twig', [
            'pagetitle' =>  "$game_name Deckbuilder",
            'pagedescription' => "Build your deck for $game_name by $publisher_name. Browse the cards and the thousand of decklists submitted by the community. Publish your own decks and get feedback.",
            'decklists_by_sphere' => $decklists_by_sphere
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
