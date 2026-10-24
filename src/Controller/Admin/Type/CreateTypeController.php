<?php

declare(strict_types=1);

namespace App\Controller\Admin\Type;

use App\Entity\Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateTypeController extends AbstractController
{
    use TypeFormsTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Creates a new Type entity.
     */
    #[Route(path: '/admin/type/create', name: 'admin_type_create', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $entity = new Type();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_type_show', ['id' => $entity->getId()]));
        }

        return $this->render('Type/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
