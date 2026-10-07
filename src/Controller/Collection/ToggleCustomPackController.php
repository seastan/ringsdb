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

class ToggleCustomPackController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CustomPackManager $customPackManager
    ) {
    }

    /**
     * @Route(
     *     "/collection/custom-pack/{id}/toggle",
     *     name="collection_custom_pack_toggle",
     *     methods={"POST"},
     *     requirements={"id"="\d+"}
     * )
     */
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $pack = $this->customPackManager->loadOwnedPack($this->currentUser(), $id);
        if (!$pack instanceof \App\Entity\UserCustomPack) {
            throw $this->createNotFoundException();
        }

        $pack->setIsEnabled(!$pack->getIsEnabled());
        $pack->setUpdatedAt(new \DateTime());

        $this->entityManager->persist($pack);
        $this->entityManager->flush();

        return $this->redirectToRoute('collection_packs');
    }
}
