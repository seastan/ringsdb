<?php

declare(strict_types=1);

namespace App\Controller\Questlog;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class ByAuthorQuestlogController extends AbstractController
{
    /**
     * @Route("/q/{username}", name="questlogs_byauthor", methods={"GET"})
     */
    public function __invoke(string $username): RedirectResponse
    {
        return $this->redirect($this->generateUrl('questlogs_list', ['type' => 'find', 'author' => $username]));
    }
}
