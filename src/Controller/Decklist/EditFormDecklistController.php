<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use App\Entity\Decklist;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EditFormDecklistController extends AbstractController
{
    /**
     * Displays the decklist edit form.
     */
    #[Route(path: '/decklist/edit/{decklist_id}', name: 'decklist_edit', requirements: ['decklist_id' => '\d+'])]
    public function __invoke(#[MapEntity(id: 'decklist_id', message: 'Decklist not found')] Decklist $decklist): Response
    {
        /* @var $user \App\Entity\User */
        $user = $this->getUser();
        if (!$user instanceof \Symfony\Component\Security\Core\User\UserInterface) {
            throw $this->createAccessDeniedException('Anonymous access denied');
        }

        if (!$this->isGranted('ROLE_SUPER_ADMIN') && !$decklist->getUser()->isEqualTo($user)) {
            throw $this->createAccessDeniedException('Access denied');
        }

        return $this->render('Decklist/decklist_edit.html.twig', ['url' => $this->generateUrl('decklist_save', ['decklist_id' => $decklist->getId()]), 'deck' => null, 'decklist' => $decklist]);
    }
}
