<?php
namespace App\Controller;

use App\Repository\ScenarioRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Command\ScrapBeornScenarioDataCommand;

class CommandController extends AbstractController {
	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function formAction(ScenarioRepository $scenarioRepository) {

        $entities = $scenarioRepository->findAll();

        return $this->render('Command/form.html.twig', [
            'entities' => $entities,
        ]);
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function runAction(Request $request) {
		$command = $request->request->get('command');
		$scenario = $request->request->get('scenario');
		$customjson = $request->request->get('customjson');
		$em = $this->getDoctrine()->getManager();
		if ($command == 'scenario') {
			$res = ScrapBeornScenarioDataCommand::command($em, $scenario, 0, $customjson);
		}
		else {
			$res = '';
		}
		return new Response($res);
	}
}