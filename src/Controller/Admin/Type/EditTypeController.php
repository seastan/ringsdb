<?php

declare(strict_types=1);

namespace App\Controller\Admin\Type;

use App\Entity\Type;
use App\Repository\TypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditTypeController extends AbstractController
{
    use TypeFormsTrait;

    public function __construct(
        private readonly TypeRepository $typeRepository
    ) {
    }

    /**
     * Displays a form to edit an existing Type entity.
     */
    #[Route(path: '/admin/type/{id}/edit', name: 'admin_type_edit')]
    public function __invoke(int $id): Response
    {
        $entity = $this->typeRepository->find($id);
        if (!$entity instanceof Type) {
            throw $this->createNotFoundException('Unable to find Type entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Type/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
