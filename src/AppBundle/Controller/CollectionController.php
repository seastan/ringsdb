<?php

namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CollectionController extends Controller {

    public function packsAction($reloaduser = false) {
        $categories = [];
        $categories[] = ["label" => "Core / Deluxe", "packs" => []];
        $repackaged = ["label" => "Repackaged", "packs" => []];
        $list_cycles = $this->getDoctrine()->getRepository('AppBundle:Cycle')->findBy([], ["position" => "ASC"]);

        // owned_packs is a per-pack COUNT map encoded as "id" / "id:count" tokens
        // (legacy "id-2"/"id-3" core copies each count as +1).
        $owned_packs = $this->getUser()->getOwnedPacks();
        $hasCollection = !empty($owned_packs);
        $countById = [];
        if ($hasCollection) {
            foreach (explode(',', $owned_packs) as $token) {
                $token = trim($token);
                if (preg_match('/^(\d+):(\d+)$/', $token, $m)) {
                    $countById[$m[1]] = (isset($countById[$m[1]]) ? $countById[$m[1]] : 0) + (int)$m[2];
                } elseif (preg_match('/^(\d+)(?:-\d+)?$/', $token, $m)) {
                    $countById[$m[1]] = (isset($countById[$m[1]]) ? $countById[$m[1]] : 0) + 1;
                }
            }
        }
        $countOf = function($pack) use ($hasCollection, $countById) {
            if ($hasCollection) {
                return isset($countById[$pack->getId()]) ? $countById[$pack->getId()] : 0;
            }
            // no collection set => default to owning one of each released pack
            return $pack->getDateRelease() !== null ? 1 : 0;
        };
        $entryOf = function($pack) use ($countOf) {
            return [
                "code" => $pack->getCode(),
                "id" => $pack->getId(),
                "label" => $pack->getName(),
                "count" => $countOf($pack),
                "future" => $pack->getDateRelease() === null,
            ];
        };

        foreach ($list_cycles as $cycle) {
            $size = count($cycle->getPacks());

            if ($cycle->getPosition() == 0 || $size == 0) {
                continue;
            }

            $first_pack = $cycle->getPacks()[0];
            if ($size === 1 && $first_pack->getName() == $cycle->getName()) {
                if ($first_pack->getIsRepackaged()) {
                    $repackaged["packs"][] = $entryOf($first_pack);
                } else {
                    $categories[0]["packs"][] = $entryOf($first_pack);
                }
            } else {
                $category = ["label" => $cycle->getName(), "packs" => []];

                foreach ($cycle->getPacks() as $pack) {
                    if ($pack->getIsRepackaged()) {
                        $repackaged["packs"][] = $entryOf($pack);
                    } else {
                        $category['packs'][] = $entryOf($pack);
                    }
                }

                if (count($category['packs'])) {
                    $categories[] = $category;
                }
            }
        }

        // Repackaged products go in their own section at the end.
        if (count($repackaged['packs'])) {
            $categories[] = $repackaged;
        }

        return $this->render('AppBundle:Collection:packs.html.twig', [
            'pagetitle' =>  "My Collection",
            'categories' => $categories,
            'reloaduser' => $reloaduser
        ]);
    }

    public function savePacksAction(Request $request) {
        $selectedPacks = $request->get('selected-packs');

        // accepts "id" / "id:count" tokens (and legacy "id-2"/"id-3")
        if (preg_match('/[^0-9:,\-]/', $selectedPacks)) {
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

    /**
     * Save the user's preferred art (printing) for a card.
     * POST card_code + pack_code; pack_code empty/"default" clears the preference.
     */
    public function saveArtPreferenceAction(Request $request) {
        $user = $this->getUser();
        if (!$user) {
            return new Response(json_encode(['success' => false, 'error' => 'not logged in']), 403, ['Content-Type' => 'application/json']);
        }

        $cardCode = preg_replace('/[^0-9]/', '', $request->get('card_code'));
        $packCode = preg_replace('/[^A-Za-z0-9_-]/', '', $request->get('pack_code'));
        if (!$cardCode) {
            return new Response(json_encode(['success' => false, 'error' => 'missing card_code']), 400, ['Content-Type' => 'application/json']);
        }

        $prefs = json_decode($user->getArtPreferences() ?: '{}', true);
        if (!is_array($prefs)) {
            $prefs = [];
        }
        if ($packCode === '' || $packCode === 'default') {
            unset($prefs[$cardCode]);
        } else {
            $prefs[$cardCode] = $packCode;
        }

        $em = $this->getDoctrine()->getManager();
        $user->setArtPreferences(empty($prefs) ? null : json_encode($prefs));
        $em->persist($user);
        $em->flush();

        return new Response(json_encode(['success' => true]), 200, ['Content-Type' => 'application/json']);
    }
}
