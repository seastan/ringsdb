<?php

declare(strict_types=1);

namespace App\Controller\Admin\Pack;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Pack;
use App\Form\PackType;
use App\Repository\PackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditPackController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly PackRepository $packRepository
    ) {
    }

    /**
     * Displays a form to edit an existing Pack entity.
     */
    #[Route(path: '/admin/pack/{id}/edit', name: 'admin_pack_edit')]
    public function __invoke(int $id): Response
    {
        $entity = $this->packRepository->find($id);
        if (!$entity instanceof Pack) {
            throw $this->createNotFoundException('Unable to find Pack entity.');
        }

        $editForm = $this->createForm(PackType::class, $entity, ['method' => 'PUT']);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('Pack/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
