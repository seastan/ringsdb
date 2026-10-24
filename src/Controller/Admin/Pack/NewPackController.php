<?php

declare(strict_types=1);

namespace App\Controller\Admin\Pack;

use App\Entity\Pack;
use App\Form\PackType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewPackController extends AbstractController
{
    /**
     * Displays a form to create a new Pack entity.
     */
    #[Route(path: '/admin/pack/new', name: 'admin_pack_new')]
    public function __invoke(): Response
    {
        $entity = new Pack();
        $form = $this->createForm(PackType::class, $entity);

        return $this->render('Pack/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
