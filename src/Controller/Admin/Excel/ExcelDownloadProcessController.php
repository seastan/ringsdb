<?php

declare(strict_types=1);

namespace App\Controller\Admin\Excel;

use App\Entity\Card;
use App\Model\ExcelDownloadDto;
use App\Repository\CardPrintingRepository;
use App\Repository\CardRepository;
use App\Repository\PackRepository;
use App\Services\Texts;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class ExcelDownloadProcessController extends AbstractController
{
    public function __construct(
        private readonly CardRepository $cardRepository,
        private readonly PackRepository $packRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/admin/excel/download', name: 'excel_download_process', methods: ['POST'])]
    public function __invoke(Texts $texts, CardPrintingRepository $cardPrintingRepository, #[MapRequestPayload] ExcelDownloadDto $payload = new ExcelDownloadDto()): StreamedResponse
    {
        $ignoredFields = ['id', 'dateCreation', 'dateUpdate'];
        $pack_id = $payload->pack;
        if (0 == $pack_id) {
            $cards = $this->cardRepository->findBy([], ['code' => 'ASC']);
            $pack_name = 'LotR LCG Cards';
        } else {
            $pack = $this->packRepository->find($pack_id);
            if (!$pack instanceof \App\Entity\Pack) {
                throw $this->createNotFoundException('Pack not found.');
            }

            $cards = $pack->getCards()->toArray();
            $pack_name = $pack->getName();
        }

        $fieldNames = $this->entityManager->getClassMetadata(Card::class)->getFieldNames();
        $associationMappings = $this->entityManager->getClassMetadata(Card::class)->getAssociationMappings();
        $lastModified = null;
        /* @var $card \App\Entity\Card */
        foreach ($cards as $card) {
            if (empty($lastModified) || $lastModified < $card->getDateUpdate()) {
                $lastModified = $card->getDateUpdate();
            }
        }

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()->setCreator('Sydtrack')->setLastModifiedBy($lastModified ? $lastModified->format('Y-m-d') : '')->setTitle($pack_name);
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
                    $getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_{$fieldName}")));
                    $value = $card->{$getter}() ? $card->{$getter}()->getName() : '';
                    $phpCell = $phpActiveSheet->getCell([$col_index++, $row_index + 2]);
                    $phpCell->setValue($value);
                }
            }

            foreach ($fieldNames as $fieldName) {
                if (in_array($fieldName, $ignoredFields)) {
                    continue;
                }

                $getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_{$fieldName}")));
                $value = $card->{$getter}();
                $value ??= '';
                $type = $this->entityManager->getClassMetadata(Card::class)->getTypeOfField($fieldName);
                $phpCell = $phpActiveSheet->getCell([$col_index++, $row_index + 2]);
                if ('code' == $fieldName) {
                    $phpCell->setValueExplicit($value, DataType::TYPE_STRING);
                } else {
                    if ('boolean' == $type) {
                        $phpCell->setValue($value ? '1' : '');
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
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $texts->slugify($pack_name).'.xlsx'));
        $response->headers->add(['Access-Control-Allow-Origin' => '*']);

        return $response;
    }
}
