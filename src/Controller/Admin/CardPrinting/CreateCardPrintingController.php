<?php

declare(strict_types=1);

namespace App\Controller\Admin\CardPrinting;

use App\Entity\CardPrinting;
use App\Entity\Pack;
use App\Form\CardPrintingType;
use App\Repository\PackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateCardPrintingController extends AbstractController
{
    use FilterPackTrait;

    public function __construct(
        private readonly PackRepository $packRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/admin/card-printing/create', name: 'admin_card_printing_create', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $filterPack = $this->resolveFilterPack($request);
        $entity = new CardPrinting();
        $form = $this->createForm(CardPrintingType::class, $entity, ['filter_pack' => $filterPack]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_card_printing_show', ['id' => $entity->getId()]));
        }

        return $this->render('CardPrinting/new.html.twig', [
            'entity' => $entity,
            'form' => $form->createView(),
            'packs' => $this->packRepository->findBy([], ['name' => 'ASC']),
            'filter_pack' => $filterPack instanceof Pack ? $filterPack->getId() : null,
        ]);
    }
}
