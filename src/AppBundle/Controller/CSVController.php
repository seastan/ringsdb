<?php
namespace AppBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Entity\Card;
use AppBundle\Entity\CardPrinting;
use AppBundle\Entity\Cycle;
use AppBundle\Entity\Pack;

class CSVController extends AbstractController {
	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function uploadFormAction() {
		return $this->render('AppBundle:CSV:upload_form.html.twig');
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function uploadProcessAction(Request $request) {
		$inputCode = $request->request->get('code');
		$inputOldCode = $request->request->get('old_code');
		$inputName = $request->request->get('name');
		$inputFileName = $request->files->get('upfile')->getPathname();
		$content = str_replace("\xEF\xBB\xBF", '', trim((string) file_get_contents($inputFileName)));
		$content = str_replace("\r", "\n", str_replace("\n", '<br/>', str_replace("\r\n", "\r", $content)));
		$content_array = explode("\n", $content);

		if (count($content_array) < 2) {
			return new Response('No cards found in the CSV file');
		}

		$columns = str_getcsv(array_shift($content_array));
		$cards = [];
		// Codes present in the CSV. Cards are identified by their unique `code`,
		// NOT by octgnid: a MotK hero (e.g. "(MotK) Ori", 99311006) and its base
		// ally ("Ori", 311006) deliberately share an octgnid but are distinct cards.
		$newCodes = [];

		foreach ($content_array as $row) {
			$card = [];
			$row = str_getcsv($row);

			for ($i = 0; $i < count($row); $i++) {
				$card[$columns[$i]] = (string) str_replace('<br/>', "\n", (string) $row[$i]);
			}

			$newCodes[$card['code']] = 1;
			array_push($cards, $card);
		}

		$em = $this->getDoctrine()->getManager();
		$packRepo = $em->getRepository('AppBundle:Pack');
		$pack = $packRepo->findOneBy(['code' => $inputCode]);
		$oldPack = $packRepo->findOneBy(['code' => $inputOldCode]);

		if (!$pack && !$oldPack) {
			$cycleRepo = $em->getRepository('AppBundle:Cycle');
			// 'ALeP' cycle code doesn't exist; fall back to the most recent cycle.
			$cycle = $cycleRepo->findOneBy(['code' => 'ALeP'])
				?? $cycleRepo->findOneBy([], ['id' => 'DESC']);

			if (!$cycle) {
				return new Response('Error: no cycle found to assign to new pack');
			}

			$pack = new Pack();
			$pack->setCode($inputCode);
			$pack->setName($inputName);
			$pack->setPosition(1);
			$pack->setSize(1);
			$pack->setDateRelease(new \DateTime('2030-02-01'));
			$pack->setCycle($cycle);
			$em->persist($pack);
			$em->flush();
		}
		elseif (!$pack) {
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

		$summary = [
			'rows' => 0,
			'cards_created' => 0,
			'printings_created' => 0,
			'updated' => 0,
			'deleted' => 0,
		];
		$createdCards = [];
		$createdPrintings = [];
		$deletedCards = [];

		// Soft-delete cards that were in this pack but are no longer in the CSV
		// (removed from the pack). Keyed by card CODE, not octgnid, so cards that
		// merely share an octgnid with a card in another pack are never touched.
		foreach ($pack->getPrintings() as $printing) {
			$existingCard = $printing->getCard();
			if (!array_key_exists($existingCard->getCode(), $newCodes) &&
				strpos($existingCard->getName(), '[deleted]') === false) {
				$deletedCards[] = $existingCard->getCode();
				$summary['deleted']++;
				$existingCard->setName('[deleted] ' . $existingCard->getName());
				$existingCard->setCode($existingCard->getCode() . '_' . uniqid());
			}
		}

		$cardRepo = $em->getRepository('AppBundle:Card');
		$printingRepo = $em->getRepository('AppBundle:CardPrinting');
		$cardMeta = $em->getClassMetadata('AppBundle:Card');
		$cardFieldNames = $cardMeta->getFieldNames();
		$cardAssocMappings = $cardMeta->getAssociationMappings();
		$printingMeta = $em->getClassMetadata('AppBundle:CardPrinting');
		$printingFieldNames = $printingMeta->getFieldNames();

		foreach ($cards as $card) {
			$summary['rows']++;
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

			// Resolve the canonical Card by its unique code. Distinct cards that
			// share an octgnid (a base ally and its MotK hero) stay separate.
			$cardEntity = $cardRepo->findOneBy(['code' => $card['code']]);
			$cardIsNew = false;
			if (!$cardEntity) {
				$cardEntity = new Card();
				$now = new \DateTime();
				$cardEntity->setDateCreation($now);
				$cardEntity->setDateUpdate($now);
				$em->persist($cardEntity);
				$cardIsNew = true;
				$createdCards[] = $card['code'];
				$summary['cards_created']++;
			}

			// One printing per (card, pack).
			$printingEntity = $printingRepo->findOneBy(['card' => $cardEntity, 'pack' => $cardPack]);
			$printingIsNew = false;
			if (!$printingEntity) {
				$printingEntity = new CardPrinting();
				$now = new \DateTime();
				$printingEntity->setDateCreation($now);
				$printingEntity->setDateUpdate($now);
				$printingEntity->setPack($cardPack);
				$printingEntity->setCard($cardEntity);
				$printingEntity->setOctgnid($card['octgnid']);
				$printingEntity->setPosition(1);
				$printingEntity->setQuantity(1);
				// imageCode is non-nullable; default to the card code and let
				// the field loop below override it from the CSV column.
				$printingEntity->setImageCode($card['imageCode'] ?? $card['image_code'] ?? $card['code'] ?? '');
				$em->persist($printingEntity);
				$printingIsNew = true;
				$createdPrintings[] = $card['code'] . ' @ ' . $cardPack->getCode();
				$summary['printings_created']++;
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
					/** @var \AppBundle\Entity\Type|\AppBundle\Entity\Sphere|null $associationEntity */
					$associationEntity = $associationRepository->findOneBy(['name' => $value]);

					if (!$associationEntity) {
						if (($colName == 'type') && ($value == 'Other')) { // legacy code
							$value = 'Contract';
							/** @var \AppBundle\Entity\Type|null $associationEntity */
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
					$type = $cardMeta->getTypeOfField((string) $colName);

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
					$type = $printingMeta->getTypeOfField((string) $colName);

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

			if (!$cardIsNew && !$printingIsNew && $changed) {
				$summary['updated']++;
			}

			$em->persist($cardEntity);
			$em->persist($printingEntity);
		}

		$em->flush();

		return $this->render('AppBundle:CSV:upload_result.html.twig', [
			'pack' => $pack,
			'summary' => $summary,
			'createdCards' => $createdCards,
			'createdPrintings' => $createdPrintings,
			'deletedCards' => $deletedCards,
		]);
	}
}
