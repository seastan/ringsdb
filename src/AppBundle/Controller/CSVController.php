<?php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Card;
use AppBundle\Entity\CardPrinting;
use AppBundle\Entity\Cycle;
use AppBundle\Entity\Pack;

class CSVController extends Controller {
	public function uploadFormAction() {
		return $this->render('AppBundle:CSV:upload_form.html.twig');
	}

	public function uploadProcessAction(Request $request) {
		$inputCode = $request->request->get('code');
		$inputOldCode = $request->request->get('old_code');
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
		$oldPack = $packRepo->findOneBy(['code' => $inputOldCode]);

		if (!$pack && !$oldPack) {
			$cycleRepo = $em->getRepository('AppBundle:Cycle');
			$cycle = $cycleRepo->findOneBy(['code' => 'ALeP']);

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
		elseif (!$pack && $oldPack) {
			$pack = $oldPack;
			$pack->setCode($inputCode);
			$em->persist($pack);
			$em->flush();
		}

		if ($pack->getName() != $inputName) {
			$pack->setName($inputName);
			$em->persist($pack);
			$em->flush();
		}

		// Build oldIds from the pack's CardPrintings (not Card rows, since a
		// canonical Card may appear in multiple packs after the refactor).
		$oldIds = [];
		foreach ($pack->getPrintings() as $printing) {
			$oldIds[$printing->getOctgnid()] = 1;
			if (!array_key_exists($printing->getOctgnid(), $newIds) &&
				strpos($printing->getCard()->getName(), '[deleted]') === false) {
				$card = $printing->getCard();
				$card->setName('[deleted] ' . $card->getName());
				$card->setCode($card->getCode() . '_' . uniqid());
			}
		}

		// Cards that existed in ALePMotKA and are now being "promoted" into a
		// main pack upload should have the MotKA printing's Card marked deleted.
		$motkPack = $packRepo->findOneBy(['code' => 'ALePMotKA']);
		if ($motkPack) {
			foreach ($motkPack->getPrintings() as $printing) {
				if (array_key_exists($printing->getOctgnid(), $oldIds) &&
					strpos($printing->getCard()->getName(), '[deleted]') === false) {
					$card = $printing->getCard();
					$card->setName('[deleted] ' . $card->getName());
					$card->setCode($card->getCode() . '_' . uniqid());
				}
			}
		}

		$printingRepo = $em->getRepository('AppBundle:CardPrinting');
		$cardMeta = $em->getClassMetadata('AppBundle:Card');
		$cardFieldNames = $cardMeta->getFieldNames();
		$cardAssocMappings = $cardMeta->getAssociationMappings();
		$printingMeta = $em->getClassMetadata('AppBundle:CardPrinting');
		$printingFieldNames = $printingMeta->getFieldNames();

		foreach ($cards as $card) {
			$changed = false;

			// Determine the target pack for this card: use the CSV 'pack'
			// column if it names a different pack, otherwise default to the
			// primary upload pack.
			$cardPack = $pack;
			if (!empty($card['pack']) && $card['pack'] !== $pack->getName()) {
				$namedPack = $packRepo->findOneBy(['name' => $card['pack']]);
				if ($namedPack) {
					$cardPack = $namedPack;
				}
			}

			// Look up by octgnid scoped to the card's target pack.
			$printingEntity = $printingRepo->findOneBy([
				'octgnid' => $card['octgnid'],
				'pack' => $cardPack,
			]);

			if ($printingEntity) {
				$cardEntity = $printingEntity->getCard();
			}
			else {
				// For cards being promoted from MotKA into a non-MotKA pack,
				// reuse the existing canonical Card rather than creating a duplicate.
				$cardEntity = null;
				if ($motkPack && $cardPack !== $motkPack) {
					$motkPrinting = $printingRepo->findOneBy([
						'octgnid' => $card['octgnid'],
						'pack' => $motkPack,
					]);
					if ($motkPrinting) {
						$cardEntity = $motkPrinting->getCard();
					}
				}

				if (!$cardEntity) {
					$cardRepo = $em->getRepository('AppBundle:Card');
					$cardEntity = $cardRepo->findOneBy(['code' => $card['code']]);
				}

				if (!$cardEntity) {
					$cardEntity = new Card();
					$now = new \DateTime();
					$cardEntity->setDateCreation($now);
					$cardEntity->setDateUpdate($now);
					$em->persist($cardEntity);
				}

				$printingEntity = new CardPrinting();
				$now = new \DateTime();
				$printingEntity->setDateCreation($now);
				$printingEntity->setDateUpdate($now);
				$printingEntity->setPack($cardPack);
				$printingEntity->setCard($cardEntity);
				$printingEntity->setPosition(1);
				$printingEntity->setQuantity(1);
				// imageCode is non-nullable; default to the card code and let
				// the field loop below override it from the CSV column.
				$printingEntity->setImageCode($card['imageCode'] ?? $card['image_code'] ?? $card['code'] ?? '');
				$em->persist($printingEntity);
				$changed = true;
			}

			foreach ($card as $colName => $value) {
				// octgnid is set on the printing at creation; pack comes from the form.
				if ($colName === 'octgnid' || $colName === 'pack') {
					continue;
				}

				$getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_$colName")));
				$setter = str_replace(' ', '', ucwords(str_replace('_', ' ', "set_$colName")));

				if (key_exists($colName, $cardAssocMappings)) {
					// Association field on Card (type, sphere).
					$associationMapping = $cardAssocMappings[$colName];
					$associationRepository = $em->getRepository($associationMapping['targetEntity']);
					$associationEntity = $associationRepository->findOneBy(['name' => $value]);

					if (!$associationEntity) {
						if (($colName == 'type') && ($value == 'Other')) { // legacy code
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

					if (!$cardEntity->$getter() || $cardEntity->$getter()->getId() !== $associationEntity->getId()) {
						$changed = true;
						$cardEntity->$setter($associationEntity);
					}
				}
				elseif (in_array($colName, $cardFieldNames)) {
					// Scalar field on Card.
					$type = $cardMeta->getTypeOfField($colName);

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

					if ($cardEntity->$getter() !== $value) {
						$changed = true;
						$cardEntity->$setter($value);
					}
				}
				elseif (in_array($colName, $printingFieldNames)) {
					// Scalar field on CardPrinting (quantity, illustrator, imageCode, …).
					$type = $printingMeta->getTypeOfField($colName);

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

					if ($printingEntity->$getter() !== $value) {
						$changed = true;
						$printingEntity->$setter($value);
					}
				}
			}

			if ($changed) {
				$em->persist($cardEntity);
				$em->persist($printingEntity);
			}
		}

		$em->flush();
		return new Response('Done');
	}
}