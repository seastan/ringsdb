<?php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Card;
use AppBundle\Entity\Cycle;
use AppBundle\Entity\Pack;

class CSVController extends Controller {
	public function uploadFormAction() {
		return $this->render('AppBundle:CSV:upload_form.html.twig');
	}

	public function uploadProcessAction(Request $request) {
		$inputCode = $request->request->get('code');
		$inputName = $request->request->get('name');
		$inputFileName = $request->files->get('upfile')->getPathname();
		$content = str_replace("\xEF\xBB\xBF", '', trim(file_get_contents($inputFileName)));
		$content = str_replace("\r", "\n", str_replace("\n", '<br/>', str_replace("\r\n", "\r", $content)));
		$content_array = explode("\n", $content);

		if (count($content_array) < 2) {
			return new Response('No cards found in the CSV file');
		}

		$columns = str_getcsv(array_shift($content_array));
		$cards = [];
		$new_ids = [];

		foreach ($content_array as $row) {
			$card = [];
			$row = str_getcsv($row);
			for ($i = 0; $i < count($row); $i++) {
				$card[$columns[$i]] = str_replace('<br/>', "\n", $row[$i]);
			}
			$new_ids[$card['code']] = 1;
			array_push($cards, $card);
		}

		$em = $this->getDoctrine()->getManager();
		$packRepo = $em->getRepository('AppBundle:Pack');
		$pack = $packRepo->findOneBy(['code' => $inputCode]);

		if (!$pack) {
			$cycleRepo = $em->getRepository('AppBundle:Cycle');
			$cycle = $cycleRepo->findOneBy(['name' => 'ALeP']);

			$pack = new Pack();
			$pack->setCode($inputCode);
			$pack->setName($inputName);
			$pack->setPosition(0);
			$pack->setSize(0);
			$pack->setCycle($cycle);
			$em->persist($pack);
			$em->flush();
		}
		elseif ($pack->getName() != $inputName) {
			$pack->setName($inputName);
			$em->persist($pack);
			$em->flush();
		}

		$oldCards = $pack->getCards();

		foreach ($oldCards as $card) {
			if ((!array_key_exists($card->getCode(), $new_ids)) &&
				(strpos($card->getName(), '[deleted]') === false)) {
				$card->setName('[deleted] ' . $card->getName());
			}
		}

		$cardRepo = $em->getRepository('AppBundle:Card');
		$metaData = $em->getClassMetadata('AppBundle:Card');
		$fieldNames = $metaData->getFieldNames();
		$associationMappings = $metaData->getAssociationMappings();

		foreach ($cards as $card) {
			$changed = false;
			$entity = $cardRepo->findOneBy(['code' => $card['code']]);

			if (!$entity) {
				$entity = new Card();
				$now = new \DateTime();
				$entity->setDateCreation($now);
				$entity->setDateUpdate($now);
			}

			foreach ($card as $colName => $value) {
				$getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_$colName")));
				$setter = str_replace(' ', '', ucwords(str_replace('_', ' ', "set_$colName")));

				if (key_exists($colName, $associationMappings)) {
					$associationMapping = $associationMappings[$colName];
					$associationRepository = $em->getRepository($associationMapping['targetEntity']);
					$associationEntity = $associationRepository->findOneBy(['name' => $value]);

					if (!$associationEntity) {
						throw new \Exception("cannot find entity [$colName] of name [$value]");
					}

					if (!$entity->$getter() || $entity->$getter()->getId() !== $associationEntity->getId()) {
						$changed = true;
						$entity->$setter($associationEntity);
					}
				}
				else {
					if (in_array($colName, $fieldNames)) {
						$type = $metaData->getTypeOfField($colName);

						if ($type === 'boolean') {
							$value = (boolean)$value;
						}
						elseif (($type === 'smallint') && ($value == '')) {
							$value = null;
						}

						if ($entity->$getter() != $value || ($entity->$getter() === null && $entity->$getter() !== $value)) {
							$changed = true;
							$entity->$setter($value);
						}
					}
				}
			}

			if ($changed) {
				$em->persist($entity);
			}
		}

		$em->flush();
		return new Response('Done');
	}
}