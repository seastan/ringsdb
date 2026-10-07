<?php

declare(strict_types=1);

namespace App\Controller\Fellowship;

use App\Controller\CurrentUserTrait;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MyListFellowshipController extends AbstractController
{
    use CurrentUserTrait;

    #[Route(path: '/myfellowships', name: 'myfellowships_list', methods: ['GET'])]
    public function __invoke(): Response
    {
        /* @var $user User */
        $user = $this->currentUser();
        /* @var $fellowships \App\Entity\Fellowship[] */
        $fellowships = $user->getFellowships();
        if (count($fellowships)) {
            return $this->render('Fellowship/my-fellowships.html.twig', ['pagetitle' => 'My Fellowships', 'pagedescription' => 'Create fellowships, a link between decks that work well together or are meant to be played together.', 'fellowships' => $fellowships]);
        }

        return $this->render('Fellowship/no-fellowships.html.twig', ['pagetitle' => 'My Fellowships', 'pagedescription' => 'Create fellowships, a link between decks that work well together or are meant to be played together.']);
    }
}
