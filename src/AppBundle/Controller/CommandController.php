<?php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Command\ScrapBeornScenarioDataCommand;
use AppBundle\Entity\Card;
use AppBundle\Entity\Cycle;
use AppBundle\Entity\Pack;

class CommandController extends Controller {
	public function formAction() {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('AppBundle:Scenario')->findAll();

        return $this->render('AppBundle:Command:form.html.twig', [
            'entities' => $entities,
        ]);
	}

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