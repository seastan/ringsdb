<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CollectionController extends Controller {

    public function packsAction($reloaduser = false, $message = null) {
        $categories = [];
        $categories[] = ["label" => "Core / Deluxe", "packs" => []];
        $list_cycles = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findBy([], ["position" => "ASC"]);

        $owned_packs = $this->getUser()->getOwnedPacks();
        if ($owned_packs) {
            $owned_packs = ','.$owned_packs.',';
        }

        foreach ($list_cycles as $cycle) {
            $size = count($cycle->getPacks());

            if ($cycle->getPosition() == 0 || $size == 0) {
                continue;
            }

            $first_pack = $cycle->getPacks()[0];
            if ($size === 1 && $first_pack->getName() == $cycle->getName()) {
                $checked = ($owned_packs) ? preg_match("/,".$first_pack->getId().",/", $owned_packs) : true;

                $categories[0]["packs"][] = ["code" => $first_pack->getCode(), "id" => $first_pack->getId(), "label" => $first_pack->getName(), "checked" => $checked, "future" => $first_pack->getDateRelease() === null];

                if ($first_pack->getCode() == 'Core') {
                    //Core 2
                    $checked = ($owned_packs) ? preg_match("/,".$first_pack->getId()."-2,/", $owned_packs) : true;
                    $categories[0]["packs"][] = ["code" => $first_pack->getCode(), "id" => $first_pack->getId().'-2', "label" => "2", "checked" => $checked, "future" => $first_pack->getDateRelease() === null];
                    //Core 3
                    $checked = ($owned_packs) ? preg_match("/,".$first_pack->getId()."-3,/", $owned_packs) : true;
                    $categories[0]["packs"][] = ["code" => $first_pack->getCode(), "id" => $first_pack->getId().'-3', "label" => "3", "checked" => $checked, "future" => $first_pack->getDateRelease() === null];
                }

            } else {
                $category = ["label" => $cycle->getName(), "packs" => []];
                foreach ($cycle->getPacks() as $pack) {
                    $checked = ($owned_packs) ? preg_match("/,".$pack->getId().",/", $owned_packs) : true;
                    $category['packs'][] = ["code" => $pack->getCode(), "id" => $pack->getId(), "label" => $pack->getName(), "checked" => $checked, "future" => $pack->getDateRelease() === null];
                }
                $categories[] = $category;
            }
        }

        return $this->render('AppBundle:Collection:packs.html.twig', [
            'pagetitle' =>  "My Collection",
            'categories' => $categories,
            'reloaduser' => $reloaduser
        ]);
    }

    public function savePacksAction(Request $request) {
        $selectedPacks = $request->get('selected-packs');

        if (preg_match('/[^0-9\-,]/', $selectedPacks)) {
            return new Response('Invalid pack selection.');
        }

        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        $user->setOwnedPacks($selectedPacks);
        $em->persist($user);
        $em->flush();

        $this->get('session')->getFlashBag()->set('notice', "Collection saved.");
        return $this->forward('AppBundle:Collection:packs', [
            'reloaduser' => true
        ]);
    }
}
