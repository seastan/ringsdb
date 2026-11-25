<?php

declare(strict_types=1);

namespace App\Controller\Admin\Card;

use App\Entity\Card;
use App\Form\CardType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewCardController extends AbstractController
{
    /**
     * Displays a form to create a new Card entity.
     */
    #[Route(path: '/admin/card/new', name: 'admin_card_new')]
    public function __invoke(): Response
    {
        $entity = new Card();
        $form = $this->createForm(CardType::class, $entity);

        return $this->render('Card/new.html.twig', ['entity' => $entity, 'form' => $form->createView()]);
    }
}
