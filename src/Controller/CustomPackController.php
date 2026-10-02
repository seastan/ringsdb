<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\UserCustomPack;
use App\Entity\UserCustomPackCard;
use App\Repository\CardRepository;
use App\Repository\UserCustomPackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CustomPackController extends AbstractController
{
    use CurrentUserTrait;
    /**
     * @var CardRepository
     */
    private $cardRepository;
    /**
     * @var UserCustomPackRepository
     */
    private $userCustomPackRepository;

    public function __construct(CardRepository $cardRepository, UserCustomPackRepository $userCustomPackRepository)
    {
        $this->cardRepository = $cardRepository;
        $this->userCustomPackRepository = $userCustomPackRepository;
    }

    /**
     * @Route("/collection/custom-pack/new", name="collection_custom_pack_new", methods={"GET"})
     */
    public function newFormAction(): Response
    {
        return $this->render('Collection/custom_pack_form.html.twig', ['pagetitle' => 'Create Custom Pack', 'pack' => null, 'save_route' => 'collection_custom_pack_save']);
    }

    /**
     * @Route("/collection/custom-pack/save", name="collection_custom_pack_save", methods={"POST"})
     */
    public function saveAction(Request $request): RedirectResponse
    {
        $user = $this->getUser();
        $em = $this->getDoctrine()->getManager();
        $name = trim($request->get('name', ''));
        if ('' === $name) {
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
        $pack->setCode('custom_'.$pack->getId().'_'.substr(md5(uniqid('', true)), 0, 6));
        $this->attachCards($em, $pack, $cardEntries);
        $em->persist($pack);
        $em->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Custom pack "'.$name.'" created.');

        return $this->redirectToRoute('collection_packs');
    }

    /**
     * @Route(
     *     "/collection/custom-pack/{id}/edit",
     *     name="collection_custom_pack_edit",
     *     methods={"GET"},
     *     requirements={"id"="\d+"}
     * )
     */
    public function editFormAction($id): Response
    {
        $pack = $this->loadOwnedPack($id);
        if (!$pack) {
            throw $this->createNotFoundException();
        }

        return $this->render('Collection/custom_pack_form.html.twig', ['pagetitle' => 'Edit Custom Pack', 'pack' => $pack, 'save_route' => 'collection_custom_pack_update']);
    }

    /**
     * @Route(
     *     "/collection/custom-pack/{id}/update",
     *     name="collection_custom_pack_update",
     *     methods={"POST"},
     *     requirements={"id"="\d+"}
     * )
     */
    public function updateAction(Request $request, $id): RedirectResponse
    {
        $pack = $this->loadOwnedPack($id);
        if (!$pack) {
            throw $this->createNotFoundException();
        }
        $name = trim($request->get('name', ''));
        if ('' === $name) {
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
        $em->flush();
        // delete old cards before inserting new ones
        $this->attachCards($em, $pack, $cardEntries);
        $em->persist($pack);
        $em->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Custom pack updated.');

        return $this->redirectToRoute('collection_packs');
    }

    /**
     * @Route(
     *     "/collection/custom-pack/{id}/delete",
     *     name="collection_custom_pack_delete",
     *     methods={"POST"},
     *     requirements={"id"="\d+"}
     * )
     */
    public function deleteAction(Request $request, $id): RedirectResponse
    {
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

    /**
     * @Route(
     *     "/collection/custom-pack/{id}/toggle",
     *     name="collection_custom_pack_toggle",
     *     methods={"POST"},
     *     requirements={"id"="\d+"}
     * )
     */
    public function toggleAction(Request $request, $id): RedirectResponse
    {
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

    /**
     * @Route(
     *     "/collection/custom-pack/{id}/publish",
     *     name="collection_custom_pack_publish",
     *     methods={"POST"},
     *     requirements={"id"="\d+"}
     * )
     */
    public function publishAction(Request $request, $id): RedirectResponse
    {
        $pack = $this->loadOwnedPack($id);
        if (!$pack) {
            throw $this->createNotFoundException();
        }
        $em = $this->getDoctrine()->getManager();
        $pack->setIsPublished(!$pack->getIsPublished());
        $pack->setUpdatedAt(new \DateTime());
        $em->persist($pack);
        $em->flush();

        return $this->redirectToRoute('collection_packs');
    }

    /**
     * @Route(
     *     "/api/public/custom-packs/published",
     *     name="api_public_custom_packs_published",
     *     methods={"GET"}
     * )
     */
    public function publishedListAction(): JsonResponse
    {
        $packs = $this->userCustomPackRepository->findBy(['isPublished' => true], ['createdAt' => 'ASC', 'id' => 'ASC']);
        $result = [];
        foreach ($packs as $pack) {
            $cards = [];
            foreach ($pack->getCards() as $entry) {
                $card = $entry->getCard();
                $sphere = $card->getSphere();
                $type = $card->getType();
                $cards[] = ['card_code' => $card->getCode(), 'card_name' => $card->getName(), 'sphere_code' => $sphere->getCode(), 'type_name' => $type->getName(), 'quantity' => $entry->getQuantity()];
            }
            $result[] = ['id' => $pack->getId(), 'name' => $pack->getName(), 'owner_name' => $pack->getUser()->getUsername(), 'cards' => $cards];
        }

        return new JsonResponse($result);
    }

    /**
     * @Route(
     *     "/collection/custom-pack/{id}/copy",
     *     name="collection_custom_pack_copy",
     *     methods={"POST"},
     *     requirements={"id"="\d+"}
     * )
     */
    public function copyAction(Request $request, $id): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Not authenticated'], 401);
        }
        $source = $this->userCustomPackRepository->findOneBy(['id' => $id, 'isPublished' => true]);
        if (!$source) {
            return new JsonResponse(['error' => 'Pack not found'], 404);
        }
        $em = $this->getDoctrine()->getManager();
        $copy = new UserCustomPack();
        $copy->setUser($user);
        $copy->setName($source->getName());
        $copy->setCode('tmp');
        $em->persist($copy);
        $em->flush();
        $copy->setCode('custom_'.$copy->getId().'_'.substr(md5(uniqid('', true)), 0, 6));
        $cardEntries = [];
        foreach ($source->getCards() as $entry) {
            $cardEntries[] = ['card_code' => $entry->getCard()->getCode(), 'quantity' => $entry->getQuantity()];
        }
        $this->attachCards($em, $copy, $cardEntries);
        $em->persist($copy);
        $em->flush();

        return new JsonResponse(['success' => true, 'name' => $copy->getName()]);
    }

    /**
     * @Route("/api/private/custom-packs", name="api_private_custom_packs", methods={"GET"})
     */
    public function apiListAction(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse([], 401);
        }
        $packs = $this->userCustomPackRepository->findBy(['user' => $user], ['createdAt' => 'ASC', 'id' => 'ASC']);
        $result = [];
        foreach ($packs as $pack) {
            $cards = [];
            foreach ($pack->getCards() as $entry) {
                $card = $entry->getCard();
                $cards[] = ['card_code' => $card->getCode(), 'card_name' => $card->getName(), 'type_code' => $card->getType()->getCode(), 'quantity' => $entry->getQuantity()];
            }
            $result[] = ['id' => $pack->getId(), 'code' => $pack->getCode(), 'name' => $pack->getName(), 'is_enabled' => $pack->getIsEnabled(), 'cards' => $cards];
        }

        return new JsonResponse($result);
    }

    private function loadOwnedPack($id): ?UserCustomPack
    {
        $pack = $this->userCustomPackRepository->find($id);
        if (!$pack || $pack->getUser()->getId() !== $this->currentUser()->getId()) {
            return null;
        }

        return $pack;
    }

    /**
     * @param array<int|string, mixed> $cardEntries
     */
    private function attachCards($em, UserCustomPack $pack, array $cardEntries): void
    {
        $cardRepo = $this->cardRepository;
        $seen = [];
        foreach ($cardEntries as $entry) {
            $code = isset($entry['card_code']) ? (string) preg_replace('/[^0-9]/', '', $entry['card_code']) : '';
            $qty = isset($entry['quantity']) ? (int) $entry['quantity'] : 1;
            if ('' === $code || $qty < 1 || $qty > 9 || isset($seen[$code])) {
                continue;
            }
            $card = $cardRepo->findOneBy(['code' => $code]);
            if (!$card) {
                continue;
            }
            $seen[$code] = true;
            $packCard = new UserCustomPackCard();
            $packCard->setCustomPack($pack);
            $packCard->setCard($card);
            $packCard->setQuantity($qty);
            $pack->addCard($packCard);
            $em->persist($packCard);
        }
    }
}
