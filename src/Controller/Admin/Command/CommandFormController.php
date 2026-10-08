<?php

declare(strict_types=1);

namespace App\Controller\Admin\Command;

use App\Repository\ScenarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CommandFormController extends AbstractController
{
    #[Route(path: '/admin/command/', name: 'command_form', methods: ['GET'])]
    public function __invoke(ScenarioRepository $scenarioRepository): Response
    {
        $entities = $scenarioRepository->findAll();

        return $this->render('Command/form.html.twig', ['entities' => $entities]);
    }
}
