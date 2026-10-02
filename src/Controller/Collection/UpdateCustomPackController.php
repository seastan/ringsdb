<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use App\Services\CustomPackManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class UpdateCustomPackController extends AbstractController
{
    use CurrentUserTrait;

    private CustomPackManager $customPackManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager,
        CustomPackManager $customPackManager
    ) {
        $this->customPackManager = $customPackManager;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route(
     *     "/collection/custom-pack/{id}/update",
     *     name="collection_custom_pack_update",
     *     methods={"POST"},
     *     requirements={"id"="\d+"}
     * )
     */
    public function __invoke(Request $request, $id): RedirectResponse
    {
        $pack = $this->customPackManager->loadOwnedPack($this->currentUser(), $id);
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
        $this->entityManager = $this->getDoctrine()->getManager();
        $pack->setName($name);
        $pack->setUpdatedAt(new \DateTime());
        $pack->clearCards();
        $this->entityManager->flush();
        // delete old cards before inserting new ones
        $this->customPackManager->attachCards($pack, $cardEntries);
        $this->entityManager->persist($pack);
        $this->entityManager->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Custom pack updated.');

        return $this->redirectToRoute('collection_packs');
    }
}
