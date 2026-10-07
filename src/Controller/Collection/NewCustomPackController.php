<?php

declare(strict_types=1);

namespace App\Controller\Collection;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewCustomPackController extends AbstractController
{
    #[Route(path: '/collection/custom-pack/new', name: 'collection_custom_pack_new', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('Collection/custom_pack_form.html.twig', ['pagetitle' => 'Create Custom Pack', 'pack' => null, 'save_route' => 'collection_custom_pack_save']);
    }
}
