<?php

declare(strict_types=1);

namespace App\Controller\Decklist;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class ByAuthorDecklistController extends AbstractController
{
    /**
     * @Route("/d/{username}", name="decklist_byauthor", methods={"GET"})
     */
    public function __invoke(string $username): RedirectResponse
    {
        return $this->redirect($this->generateUrl('decklists_list', ['type' => 'find', 'author' => $username]));
    }
}
