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
		/* @var $uploadedFile \Symfony\Component\HttpFoundation\File\UploadedFile */
		$inputCode = $request->request->get('code');
		$inputName = $request->request->get('name');
		$inputFileName = $request->files->get('upfile')->getPathname();
		$content = file_get_contents($inputFileName);
		$content = str_replace("\r", "\n", str_replace("\n", "<br/>", str_replace("\r\n", "\r", str_replace("\xEF\xBB\xBF", '', trim($content)))));
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
				$card[$columns[$i]] = str_replace("<br/>", "\n", $row[$i]);
			}
			$new_ids[$card['octgnid']] = 1;
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
		}
		elseif ($pack->getName() != $inputName) {
			$pack->setName($inputName);
			$em->persist($pack);
		}

		$oldCards = $pack->getCards();
		foreach ($oldCards as $card) {
			if (!array_key_exists($card->getOctgnid(), $new_ids)) {
				$card->setName('[deleted]' . $card->getName());
			}
		}

		$em->flush();
		return new Response('Done');

		// analysis of first row
		$colNames = [];

		$cards = [];
		$firstRow = true;
		foreach ($objWorksheet->getRowIterator() as $row) {
			// dismiss first row (titles)
			if ($firstRow) {
				$firstRow = false;

				// analysis of first row
				foreach ($row->getCellIterator() as $cell) {
					$colNames[$cell->getColumn()] = $cell->getValue();
				}
				continue;
			}

			$card = [];

			$cellIterator = $row->getCellIterator();
			foreach ($cellIterator as $cell) {
				$col = $cell->getColumn();
				$colName = $colNames[$col];

				//$setter = str_replace(' ', '', ucwords(str_replace('_', ' ', "set_$fieldName")));
				$card[$colName] = $cell->getValue();
			}
			if (count($card) && !empty($card['code'])) {
				$cards[] = $card;
			}
		}

		/* @var $em \Doctrine\ORM\EntityManager */
		$em = $this->getDoctrine()->getManager();
		$repo = $em->getRepository('AppBundle:Card');

		$metaData = $em->getClassMetadata('AppBundle:Card');
		$fieldNames = $metaData->getFieldNames();
		$associationMappings = $metaData->getAssociationMappings();

		$counter = 0;
		foreach ($cards as $card) {
			/* @var $entity \AppBundle\Entity\Card */
			$entity = $repo->findOneBy(['code' => $card['code']]);
			if (!$entity) {
				if ($enableCardCreation) {
					$entity = new Card();
					$now = new \DateTime();
					$entity->setDateCreation($now);
					$entity->setDateUpdate($now);
				} else {
					continue;
				}
			}

			$changed = false;
			$output = ["<h4>" . $card['name'] . "</h4>"];

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
						$output[] = "<p>association [$colName] changed</p>";

						$entity->$setter($associationEntity);
					}
				} else {
					if (in_array($colName, $fieldNames)) {
						$type = $metaData->getTypeOfField($colName);
						if ($type === 'boolean') {
							$value = (boolean)$value;
						}
						if ($entity->$getter() != $value || ($entity->$getter() === null && $entity->$getter() !== $value)) {
							$changed = true;
							$output[] = "<p>field [$colName] changed</p>";

							$entity->$setter($value);
						}
					}
				}
			}

			if ($changed) {
				$em->persist($entity);
				$counter++;

				echo join("", $output);
			}
		}

		$em->flush();

		return new Response($counter . " cards changed or added");
	}
}
