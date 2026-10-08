<?php

declare(strict_types=1);

namespace App\Controller\Admin\Encounter;

use App\Entity\Encounter;
use App\Form\EncounterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateEncounterController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Creates a new Encounter entity.
     */
    #[Route(path: '/admin/encounter/create', name: 'admin_encounter_create', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $entity = new Encounter();
        $form = $this->createForm(EncounterType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_encounter_show', ['id' => $entity->getId()]));
        }

        return $this->render('Encounter/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
