<?php

declare(strict_types=1);

namespace App\Controller\Admin\Card;

use App\Entity\Card;
use App\Form\CardType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CreateCardController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    /**
     * Creates a new Card entity.
     */
    #[Route(path: '/admin/card/create', name: 'admin_card_create', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $entity = new Card();
        $form = $this->createForm(CardType::class, $entity);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($entity);
            $this->entityManager->flush();

            return $this->redirect($this->generateUrl('admin_card_show', ['id' => $entity->getId()]));
        }

        return $this->render('Card/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
