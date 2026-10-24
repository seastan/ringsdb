<?php

declare(strict_types=1);

namespace App\Controller\Admin\Encounter;

use App\Entity\Encounter;
use App\Form\EncounterType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewEncounterController extends AbstractController
{
    /**
     * Displays a form to create a new Encounter entity.
     */
    #[Route(path: '/admin/encounter/new', name: 'admin_encounter_new')]
    public function __invoke(): Response
    {
        $entity = new Encounter();
        $form = $this->createForm(EncounterType::class, $entity);

        return $this->render('Encounter/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
