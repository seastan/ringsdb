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
		$newIds = [];

		foreach ($content_array as $row) {
			$card = [];
			$row = str_getcsv($row);

			for ($i = 0; $i < count($row); $i++) {
				$card[$columns[$i]] = str_replace('<br/>', "\n", $row[$i]);
			}

			$newIds[$card['octgnid']] = 1;
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
			$pack->setPosition(1);
			$pack->setSize(1);
			$pack->setDateRelease(date_create('2030-02-01'));
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
		$oldIds = [];
		$motkPack = $packRepo->findOneBy(['code' => 'MotKA']);
		$oldMotkCards = $motkPack->getCards();

		foreach ($oldCards as $card) {
			$oldIds[$card->getOctgnid()] = 1;

			if (!array_key_exists($card->getOctgnid(), $newIds) &&
				(strpos($card->getName(), '[deleted]') === false)) {
				$card->setName('[deleted] ' . $card->getName());
				$card->setCode($card->getCode() . '_' . uniqid());
			}
		}

		foreach ($oldMotkCards as $card) {
			if (array_key_exists($card->getOctgnid(), $oldIds) &&
				(strpos($card->getName(), '[deleted]') === false)) {
				$card->setName('[deleted] ' . $card->getName());
				$card->setCode($card->getCode() . '_' . uniqid());
			}
		}

		$cardRepo = $em->getRepository('AppBundle:Card');
		$metaData = $em->getClassMetadata('AppBundle:Card');
		$fieldNames = $metaData->getFieldNames();
		$associationMappings = $metaData->getAssociationMappings();

		foreach ($cards as $card) {
			$changed = false;
			$entities = $cardRepo->findBy(['octgnid' => $card['octgnid']]);
			$entity = null;
			if ($entities) {
				foreach ($entities as $candidate) {
					if (($card['pack'] == 'ALeP - Messenger of the King Allies') &&
						($candidate->getPack()->getName() == 'ALeP - Messenger of the King Allies')) {
						$entity = $candidate;
						break;
					}
					elseif (($card['pack'] != 'ALeP - Messenger of the King Allies') &&
						($candidate->getPack()->getName() != 'ALeP - Messenger of the King Allies')) {
						$entity = $candidate;
						break;
					}
				}
			}

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
						if (($colName == 'type') && ($value == 'Other')) {
							$value = 'Contract';
							$associationEntity = $associationRepository->findOneBy(['name' => $value]);
							if (!$associationEntity) {
								throw new \Exception("cannot find entity [$colName] of name [$value]");
							}
						}
						else {
							throw new \Exception("cannot find entity [$colName] of name [$value]");
						}
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
						elseif (($type === 'smallint') && ($value == 'X')) {
							$value = null;
						}
						elseif (($colName == 'cost') && ($value == '')) {
							$value = null;
						}

						if ($entity->$getter() !== $value) {
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