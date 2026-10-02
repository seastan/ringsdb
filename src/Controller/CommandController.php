<?php

declare(strict_types=1);

namespace App\Controller;

use App\Command\ScrapBeornScenarioDataCommand;
use App\Repository\ScenarioRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CommandController extends AbstractController
{
    /**
     * @return Response
     *
     * @Route("/admin/command/", name="command_form", methods={"GET"})
     */
    public function formAction(ScenarioRepository $scenarioRepository)
    {
        $entities = $scenarioRepository->findAll();

        return $this->render('Command/form.html.twig', ['entities' => $entities]);
    }

    /**
     * @return Response
     *
     * @Route("/admin/command/", name="command_run", methods={"POST"})
     */
    public function runAction(Request $request)
    {
        $command = $request->request->get('command');
        $scenario = $request->request->get('scenario');
        $customjson = $request->request->get('customjson');
        $em = $this->getDoctrine()->getManager();
        if ('scenario' == $command) {
            $res = ScrapBeornScenarioDataCommand::command($em, $scenario, 0, $customjson);
        } else {
            $res = '';
        }

        return new Response($res);
    }
}
