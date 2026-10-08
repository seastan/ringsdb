<?php

declare(strict_types=1);

namespace App\Controller\Admin\Command;

use App\Command\ScrapBeornScenarioDataCommand;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CommandRunController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/admin/command/', name: 'command_run', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        $command = $request->request->get('command');
        $scenario = (string) $request->request->get('scenario');
        $customjson = (string) $request->request->get('customjson');
        if ('scenario' == $command) {
            $res = ScrapBeornScenarioDataCommand::command($this->entityManager, $scenario, 0, $customjson);
        } else {
            $res = '';
        }

        return new Response($res);
    }
}
