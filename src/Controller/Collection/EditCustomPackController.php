<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use App\Entity\UserCustomPack;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditCustomPackController extends AbstractController
{
    use CurrentUserTrait;

    #[Route(path: '/collection/custom-pack/{id}/edit', name: 'collection_custom_pack_edit', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function __invoke(UserCustomPack $pack): Response
    {
        if (!$pack->getUser()->isEqualTo($this->currentUser())) {
            throw $this->createNotFoundException();
        }

        return $this->render('Collection/custom_pack_form.html.twig', ['pagetitle' => 'Edit Custom Pack', 'pack' => $pack, 'save_route' => 'collection_custom_pack_update']);
    }
}
