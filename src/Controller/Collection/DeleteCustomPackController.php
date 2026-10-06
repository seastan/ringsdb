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

class DeleteCustomPackController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(private EntityManagerInterface $entityManager, private CustomPackManager $customPackManager)
    {
    }

    /**
     * @Route(
     *     "/collection/custom-pack/{id}/delete",
     *     name="collection_custom_pack_delete",
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

        $this->entityManager->remove($pack);
        $this->entityManager->flush();
        $this->get('session')->getFlashBag()->set('notice', 'Custom pack deleted.');

        return $this->redirectToRoute('collection_packs');
    }
}
