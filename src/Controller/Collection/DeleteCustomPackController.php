<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use App\Entity\UserCustomPack;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DeleteCustomPackController extends AbstractController
{
    use CurrentUserTrait;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/collection/custom-pack/{id}/delete', name: 'collection_custom_pack_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(Request $request, UserCustomPack $pack): RedirectResponse
    {
        if (!$pack->getUser()->isEqualTo($this->currentUser())) {
            throw $this->createNotFoundException();
        }

        $this->entityManager->remove($pack);
        $this->entityManager->flush();
        $this->addFlash('notice', 'Custom pack deleted.');

        return $this->redirectToRoute('collection_packs');
    }
}
