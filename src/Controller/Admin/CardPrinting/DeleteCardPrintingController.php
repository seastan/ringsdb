<?php

declare(strict_types=1);

namespace App\Controller\Admin\CardPrinting;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\CardPrinting;
use App\Repository\CardPrintingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DeleteCardPrintingController extends AbstractController
{
    use DeleteFormTrait;

    public function __construct(
        private readonly CardPrintingRepository $cardPrintingRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/admin/card-printing/{id}/delete', name: 'admin_card_printing_delete', methods: ['POST', 'DELETE'])]
    public function __invoke(Request $request, int $id): RedirectResponse
    {
        $form = $this->createDeleteForm($id);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entity = $this->cardPrintingRepository->find($id);
            if (!$entity instanceof CardPrinting) {
                throw $this->createNotFoundException('Unable to find CardPrinting entity.');
            }

            $this->entityManager->remove($entity);
            $this->entityManager->flush();
        }

        return $this->redirect($this->generateUrl('admin_card_printing'));
    }
}
