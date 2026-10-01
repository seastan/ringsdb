<?php

namespace App\Controller;

use App\Repository\PackRepository;
use App\Repository\CardPrintingRepository;
use App\Repository\CardRepository;
use App\Entity\Pack;
use App\Services\Texts;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Card;

class ExcelController extends AbstractController {
	/**
	 * @var CardRepository
	 */
	private $cardRepository;

	/**
	 * @var PackRepository
	 */
	private $packRepository;

	public function __construct(CardRepository $cardRepository, PackRepository $packRepository) {
		$this->cardRepository = $cardRepository;
		$this->packRepository = $packRepository;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function downloadFormAction() {
		$packs = $this->packRepository->findBy([], ['dateRelease' => 'ASC', 'name' => 'ASC']);

		return $this->render('Excel/download_form.html.twig', [
			'packs' => $packs
		]);
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\StreamedResponse
	 */
	public function downloadProcessAction(Request $request, Texts $texts, CardPrintingRepository $cardPrintingRepository) {
		$ignoredFields = ['id', 'dateCreation', 'dateUpdate'];

		$em = $this->getDoctrine()->getManager();

		$pack_id = $request->request->get('pack');
		if ($pack_id == 0) {
			$cards = $this->cardRepository->findBy([], ['code' => 'ASC']);
			$pack_name = 'LotR LCG Cards';
		} else {
			$pack = $this->packRepository->find($pack_id);
			if (!$pack) {
				throw $this->createNotFoundException('Pack not found.');
			}
			$printings = $cardPrintingRepository->findBy(['pack' => $pack], ['position' => 'ASC']);
			$cards = array_values(array_unique(array_map(function($p) { return $p->getCard(); }, $printings), SORT_REGULAR));
			$pack_name = $pack->getName();
		}

		$fieldNames = $em->getClassMetadata(Card::class)->getFieldNames();

		$associationMappings = $em->getClassMetadata(Card::class)->getAssociationMappings();

		$lastModified = null;
		/* @var $card \App\Entity\Card */
		foreach ($cards as $card) {
			if (empty($lastModified) || $lastModified < $card->getDateUpdate()) {
				$lastModified = $card->getDateUpdate();
			}
		}

		$spreadsheet = new Spreadsheet();
		$spreadsheet->getProperties()->setCreator("Sydtrack")->setLastModifiedBy($lastModified ? $lastModified->format('Y-m-d') : '')->setTitle($pack_name);
		$phpActiveSheet = $spreadsheet->setActiveSheetIndex(0);
		$phpActiveSheet->setTitle(mb_substr($pack_name, 0, 31));

		// PhpSpreadsheet columns start at 1
		$col_index = 1;
		foreach ($associationMappings as $fieldName => $associationMapping) {
			if ($associationMapping['isOwningSide']) {
				$phpCell = $phpActiveSheet->getCell([$col_index++, 1]);
				$phpCell->setValue($fieldName);
			}
		}
		foreach ($fieldNames as $fieldName) {
			if (in_array($fieldName, $ignoredFields)) {
				continue;
			}
			$phpCell = $phpActiveSheet->getCell([$col_index++, 1]);
			$phpCell->setValue($fieldName);
		}

		foreach ($cards as $row_index => $card) {
			$col_index = 1;
			foreach ($associationMappings as $fieldName => $associationMapping) {
				if ($associationMapping['isOwningSide']) {
					$getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_$fieldName")));
					$value = $card->$getter() ? $card->$getter()->getName() : '';

					$phpCell = $phpActiveSheet->getCell([$col_index++, $row_index + 2]);
					$phpCell->setValue($value);
				}
			}
			foreach ($fieldNames as $fieldName) {
				if (in_array($fieldName, $ignoredFields)) {
					continue;
				}

				$getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_$fieldName")));
				$value = $card->$getter();
				if (!isset($value)) {
					$value = '';
				}
				$type = $em->getClassMetadata(Card::class)->getTypeOfField($fieldName);

				$phpCell = $phpActiveSheet->getCell([$col_index++, $row_index + 2]);
				if ($fieldName == 'code') {
					$phpCell->setValueExplicit($value, DataType::TYPE_STRING);
				} else {
					if ($type == 'boolean') {
						$phpCell->setValue($value ? "1" : "");
					} else {
						$phpCell->setValue($value);
					}
				}
			}
		}

		$writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
		$response = new StreamedResponse(function () use ($writer): void {
			$writer->save('php://output');
		});
		$response->headers->set('Content-Type', 'text/vnd.ms-excel; charset=utf-8');
		$response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $texts->slugify($pack_name) . '.xlsx'));
		$response->headers->add(['Access-Control-Allow-Origin' => '*']);

		return $response;
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function uploadFormAction() {
		return $this->render('Excel/upload_form.html.twig');
	}

	/**
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function uploadProcessAction(Request $request) {
		/* @var $uploadedFile \Symfony\Component\HttpFoundation\File\UploadedFile */
		$uploadedFile = $request->files->get('upfile');
		$inputFileName = $uploadedFile->getPathname();
		$objReader = IOFactory::createReaderForFile($inputFileName);
		$objReader->setReadDataOnly(true);
		$spreadsheet = $objReader->load($inputFileName);
		$objWorksheet = $spreadsheet->getActiveSheet();

		$enableCardCreation = $request->request->has('create');

		// analysis of first row
		$colNames = [];

		$cards = [];
		$firstRow = true;
		foreach ($objWorksheet->getRowIterator() as $row) {
			// dismiss first row (titles)
			if ($firstRow) {
				$firstRow = false;

				// analysis of first row
				$cellIterator = $row->getCellIterator();
				foreach ($cellIterator as $cell) {
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
		$repo = $this->cardRepository;

		$metaData = $em->getClassMetadata(Card::class);
		$fieldNames = $metaData->getFieldNames();
		$associationMappings = $metaData->getAssociationMappings();

		$counter = 0;
		foreach ($cards as $card) {
			/* @var $entity \App\Entity\Card */
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

				if (array_key_exists($colName, $associationMappings)) {
					$associationMapping = $associationMappings[$colName];

					/** @var class-string<\App\Entity\Type|\App\Entity\Sphere> $targetEntity */
					$targetEntity = $associationMapping['targetEntity'];
					$associationRepository = $em->getRepository($targetEntity);
					/** @var \App\Entity\Type|\App\Entity\Sphere|null $associationEntity */
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
						$type = $metaData->getTypeOfField((string) $colName);
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

				echo implode("", $output);
			}
		}

		$em->flush();

		return new Response($counter . " cards changed or added");
	}
}
