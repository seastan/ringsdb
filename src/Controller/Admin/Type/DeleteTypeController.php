<?php

declare(strict_types=1);

namespace App\Controller\Admin\Type;

use App\Entity\Type;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DeleteTypeController extends AbstractController
{
    use TypeFormsTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Deletes a Type entity.
     */
    #[Route(path: '/admin/type/{id}/delete', name: 'admin_type_delete', methods: ['POST', 'DELETE'])]
    public function __invoke(Request $request, #[MapEntity(message: 'Unable to find Type entity.')] Type $entity): RedirectResponse
    {
        $form = $this->createDeleteForm($entity->getId());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_type'));
    }
}
