<?php

declare(strict_types=1);

namespace App\Controller\Admin\CardPrinting;

use App\Controller\Admin\DeleteFormTrait;
use App\Entity\CardPrinting;
use App\Entity\Pack;
use App\Form\CardPrintingType;
use App\Model\FilterPackDto;
use App\Repository\PackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

class UpdateCardPrintingController extends AbstractController
{
    use FilterPackTrait;
    use DeleteFormTrait;

    public function __construct(
        private readonly PackRepository $packRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/admin/card-printing/{id}/update', name: 'admin_card_printing_update', methods: ['POST', 'PUT'])]
    public function __invoke(Request $request, #[MapEntity(message: 'Unable to find CardPrinting entity.')] CardPrinting $entity, #[MapQueryString] FilterPackDto $query = new FilterPackDto()): Response
    {
        $filterPack = $this->resolveFilterPack($query);
        $deleteForm = $this->createDeleteForm($entity->getId());
        $editForm = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack, 'method' => 'PUT']);
        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_card_printing_edit', ['id' => $entity->getId()]));
        }

        return $this->render('CardPrinting/edit.html.twig', ['entity' => $entity, 'edit_form' => $editForm->createView(), 'delete_form' => $deleteForm->createView(), 'packs' => $this->packRepository->findBy([], ['name' => 'ASC']), 'filter_pack' => $filterPack instanceof Pack ? $filterPack->getId() : null]);
    }
}
