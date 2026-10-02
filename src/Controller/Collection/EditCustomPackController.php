<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use App\Controller\CurrentUserTrait;
use App\Services\CustomPackManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EditCustomPackController extends AbstractController
{
    use CurrentUserTrait;

    private CustomPackManager $customPackManager;

    public function __construct(
        CustomPackManager $customPackManager
    ) {
        $this->customPackManager = $customPackManager;
    }

    /**
     * @Route(
     *     "/collection/custom-pack/{id}/edit",
     *     name="collection_custom_pack_edit",
     *     methods={"GET"},
     *     requirements={"id"="\d+"}
     * )
     */
    public function __invoke($id): Response
    {
        $pack = $this->customPackManager->loadOwnedPack($this->currentUser(), $id);
        if (!$pack) {
            throw $this->createNotFoundException();
        }

        return $this->render('Collection/custom_pack_form.html.twig', ['pagetitle' => 'Edit Custom Pack', 'pack' => $pack, 'save_route' => 'collection_custom_pack_update']);
    }
}
