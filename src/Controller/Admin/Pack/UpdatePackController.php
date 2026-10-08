<?php

declare(strict_types=1);

namespace App\Controller\Admin\Pack;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Pack;
use App\Form\PackType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UpdatePackController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Edits an existing Pack entity.
     */
    #[Route(path: '/admin/pack/{id}/update', name: 'admin_pack_update', methods: ['POST', 'PUT'])]
    public function __invoke(Request $request, #[MapEntity(message: 'Unable to find Pack entity.')] Pack $entity): Response
    {
        $deleteForm = $this->createDeleteForm($entity->getId());
        $editForm = $this->createForm(PackType::class, $entity, ['method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_pack_edit', ['id' => $entity->getId()]));
        }

        return $this->render('Pack/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView()]);
    }
}
