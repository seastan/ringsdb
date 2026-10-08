<?php

declare(strict_types=1);

namespace App\Controller\Admin\Cycle;

use App\Entity\Cycle;
use App\Form\CycleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateCycleController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Creates a new Cycle entity.
     */
    #[Route(path: '/admin/cycle/create', name: 'admin_cycle_create', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $entity = new Cycle();
        $form = $this->createForm(CycleType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_cycle_show', ['id' => $entity->getId()]));
        }

        return $this->render('Cycle/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
