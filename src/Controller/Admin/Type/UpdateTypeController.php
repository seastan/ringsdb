<?php

declare(strict_types=1);

namespace App\Controller\Admin\Type;

use App\Entity\Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UpdateTypeController extends AbstractController
{
    use TypeFormsTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Edits an existing Type entity.
     */
    #[Route(path: '/admin/type/{id}/update', name: 'admin_type_update', methods: ['POST', 'PUT'])]
    public function __invoke(Request $request, #[MapEntity(message: 'Unable to find Type entity.')] Type $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_type_edit', ['id' => $entity->getId()]));
        }

        return $this->render('Type/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
