<?php

declare(strict_types=1);

namespace App\Controller\Admin\Cycle;

use App\Entity\Cycle;
use App\Form\CycleType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewCycleController extends AbstractController
{
    /**
     * Displays a form to create a new Cycle entity.
     */
    #[Route(path: '/admin/cycle/new', name: 'admin_cycle_new')]
    public function __invoke(): Response
    {
        $entity = new Cycle();
        $form = $this->createForm(CycleType::class, $entity);

        return $this->render('Cycle/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
