<?php

declare(strict_types=1);

namespace App\Controller\Admin\Card;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\Card;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DeleteCardController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Deletes a Card entity.
     */
    #[Route(path: '/admin/card/{id}/delete', name: 'admin_card_delete', methods: ['POST', 'DELETE'])]
    public function __invoke(Request $request, #[MapEntity(message: 'Unable to find Card entity.')] Card $entity): RedirectResponse
    {
        $form = $this->createDeleteForm($entity->getId());
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_card'));
    }
}
