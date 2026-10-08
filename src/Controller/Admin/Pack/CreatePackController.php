<?php

declare(strict_types=1);

namespace App\Controller\Admin\Pack;

use App\Entity\Pack;
use App\Form\PackType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreatePackController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Creates a new Pack entity.
     */
    #[Route(path: '/admin/pack/create', name: 'admin_pack_create', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $entity = new Pack();
        $form = $this->createForm(PackType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_pack_show', ['id' => $entity->getId()]));
        }

        return $this->render('Pack/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
