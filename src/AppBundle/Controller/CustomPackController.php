<?php

namespace AppBundle\Controller;

use AppBundle\Entity\UserCustomPack;
use AppBundle\Entity\UserCustomPackCard;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomPackController extends Controller {

    public function newFormAction() {
        return $this->render('AppBundle:Collection:custom_pack_form.html.twig', [
            'pagetitle' => 'Create Custom Pack',
            'pack' => null,
            'save_route' => 'collection_custom_pack_save',
        ]);
    }

    public function saveAction(Request $request) {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();

        $name = trim($request->get('name', ''));
        if ($name === '') {
            $this->get('session')->getFlashBag()->set('error', 'Pack name is required.');
            return $this->redirectToRoute('collection_custom_pack_new');
        }

        $cardsJson = $request->get('cards_json', '[]');
        $cardEntries = json_decode($cardsJson, true);
        if (!is_array($cardEntries)) {
            $cardEntries = [];
        }

        $pack = new UserCustomPack();
        $pack->setUser($user);
        $pack->setName($name);
        $pack->setCode('tmp');

        $em->persist($pack);
        $em->flush();

        $pack->setCode('custom_' . $pack->getId() . '_' . substr(md5(uniqid('', true)), 0, 6));
        $this->attachCards($em, $pack, $cardEntries);

        $em->persist($pack);
        $em->flush();

        $this->get('session')->getFlashBag()->set('notice', 'Custom pack "' . $name . '" created.');
        return $this->redirectToRoute('collection_packs');
    }

    public function editFormAction($id) {
        $pack = $this->loadOwnedPack($id);
        if (!$pack) {
            throw $this->createNotFoundException();
        }

        return $this->render('AppBundle:Collection:custom_pack_form.html.twig', [
            'pagetitle' => 'Edit Custom Pack',
            'pack' => $pack,
            'save_route' => 'collection_custom_pack_update',
        ]);
    }

    public function updateAction(Request $request, $id) {
        $pack = $this->loadOwnedPack($id);
        if (!$pack) {
            throw $this->createNotFoundException();
        }

        $name = trim($request->get('name', ''));
        if ($name === '') {
            $this->get('session')->getFlashBag()->set('error', 'Pack name is required.');
            return $this->redirectToRoute('collection_custom_pack_edit', ['id' => $id]);
        }

        $cardsJson = $request->get('cards_json', '[]');
        $cardEntries = json_decode($cardsJson, true);
        if (!is_array($cardEntries)) {
            $cardEntries = [];
        }

        $em = $this->getDoctrine()->getManager();
        $pack->setName($name);
        $pack->setUpdatedAt(new \DateTime());
        $pack->clearCards();
        $this->attachCards($em, $pack, $cardEntries);

        $em->persist($pack);
        $em->flush();

        $this->get('session')->getFlashBag()->set('notice', 'Custom pack updated.');
        return $this->redirectToRoute('collection_packs');
    }

    public function deleteAction(Request $request, $id) {
        $pack = $this->loadOwnedPack($id);
        if (!$pack) {
            throw $this->createNotFoundException();
        }

        $em = $this->getDoctrine()->getManager();
        $em->remove($pack);
        $em->flush();

        $this->get('session')->getFlashBag()->set('notice', 'Custom pack deleted.');
        return $this->redirectToRoute('collection_packs');
    }

    public function toggleAction(Request $request, $id) {
        $pack = $this->loadOwnedPack($id);
        if (!$pack) {
            throw $this->createNotFoundException();
        }

        $em = $this->getDoctrine()->getManager();
        $pack->setIsEnabled(!$pack->getIsEnabled());
        $pack->setUpdatedAt(new \DateTime());
        $em->persist($pack);
        $em->flush();

        return $this->redirectToRoute('collection_packs');
    }

    public function apiListAction() {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([], 401);
        }

        $packs = $this->getDoctrine()
            ->getRepository('AppBundle:UserCustomPack')
            ->findBy(['user' => $user], ['createdAt' => 'ASC']);

        $result = [];
        foreach ($packs as $pack) {
            $cards = [];
            foreach ($pack->getCards() as $entry) {
                $card = $entry->getCard();
                $cards[] = [
                    'card_code' => $card->getCode(),
                    'card_name' => $card->getName(),
                    'type_code' => $card->getType() ? $card->getType()->getCode() : null,
                    'quantity' => $entry->getQuantity(),
                ];
            }
            $result[] = [
                'id' => $pack->getId(),
                'code' => $pack->getCode(),
                'name' => $pack->getName(),
                'is_enabled' => $pack->getIsEnabled(),
                'cards' => $cards,
            ];
        }

        return new JsonResponse($result);
    }

    private function loadOwnedPack($id) {
        $pack = $this->getDoctrine()
            ->getRepository('AppBundle:UserCustomPack')
            ->find($id);

        if (!$pack || $pack->getUser()->getId() !== $this->getUser()->getId()) {
            return null;
        }

        return $pack;
    }

    private function attachCards($em, UserCustomPack $pack, array $cardEntries) {
        $cardRepo = $this->getDoctrine()->getRepository('AppBundle:Card');
        foreach ($cardEntries as $entry) {
            $code = isset($entry['card_code']) ? preg_replace('/[^0-9]/', '', $entry['card_code']) : '';
            $qty = isset($entry['quantity']) ? (int)$entry['quantity'] : 1;
            if ($code === '' || $qty < 1 || $qty > 9) {
                continue;
            }
            $card = $cardRepo->findOneBy(['code' => $code]);
            if (!$card) {
                continue;
            }
            $packCard = new UserCustomPackCard();
            $packCard->setCustomPack($pack);
            $packCard->setCard($card);
            $packCard->setQuantity($qty);
            $pack->addCard($packCard);
            $em->persist($packCard);
        }
    }
}
