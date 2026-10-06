<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Command\ScrapBeornScenarioDataCommand;
use App\Repository\ScenarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommandController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    /**
     * @Route("/admin/command/", name="command_form", methods={"GET"})
     */
    public function formAction(ScenarioRepository $scenarioRepository): Response
    {
        $entities = $scenarioRepository->findAll();

        return $this->render('Command/form.html.twig', ['entities' => $entities]);
    }

    /**
     * @Route("/admin/command/", name="command_run", methods={"POST"})
     */
    public function runAction(Request $request): Response
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
