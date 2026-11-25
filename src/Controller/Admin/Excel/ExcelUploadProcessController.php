<?php

declare(strict_types=1);

namespace App\Controller\Admin\Excel;

use App\Entity\Card;
use App\Entity\Sphere;
use App\Entity\Type;
use App\Model\ExcelUploadDto;
use App\Repository\CardRepository;
use Doctrine\ORM\EntityManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Attribute\MapUploadedFile;
use Symfony\Component\Routing\Attribute\Route;

class ExcelUploadProcessController extends AbstractController
{
    public function __construct(
        private readonly CardRepository $cardRepository,
        private readonly EntityManagerInterface $entityManager
    ) {
    }

    #[Route(path: '/admin/excel/upload', name: 'excel_upload_process', methods: ['POST'])]
    public function __invoke(#[MapUploadedFile] UploadedFile $upfile, #[MapRequestPayload] ExcelUploadDto $payload = new ExcelUploadDto()): Response
    {
        $inputFileName = $upfile->getPathname();
        $objReader = IOFactory::createReaderForFile($inputFileName);
        $objReader->setReadDataOnly(true);

        $spreadsheet = $objReader->load($inputFileName);
        $objWorksheet = $spreadsheet->getActiveSheet();
        $enableCardCreation = null !== $payload->create;
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
                // $setter = str_replace(' ', '', ucwords(str_replace('_', ' ', "set_$fieldName")));
                $card[$colName] = $cell->getValue();
            }

            if (count($card) && !empty($card['code'])) {
                $cards[] = $card;
            }
        }

        $repo = $this->cardRepository;
        $metaData = $this->entityManager->getClassMetadata(Card::class);
        $fieldNames = $metaData->getFieldNames();
        $associationMappings = $metaData->getAssociationMappings();
        $counter = 0;
        foreach ($cards as $card) {
            /* @var $entity \App\Entity\Card */
            $entity = $repo->findOneBy(['code' => $card['code']]);
            if (!$entity instanceof Card) {
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
            $output = ['<h4>'.$card['name'].'</h4>'];
            foreach ($card as $colName => $value) {
                $getter = str_replace(' ', '', ucwords(str_replace('_', ' ', "get_{$colName}")));
                $setter = str_replace(' ', '', ucwords(str_replace('_', ' ', "set_{$colName}")));
                if (array_key_exists($colName, $associationMappings)) {
                    $associationMapping = $associationMappings[$colName];
                    /** @var class-string<Type|Sphere> $targetEntity */
                    $targetEntity = $associationMapping['targetEntity'];
                    $associationRepository = $this->entityManager->getRepository($targetEntity);
                    /** @var Type|Sphere|null $associationEntity */
                    $associationEntity = $associationRepository->findOneBy(['name' => $value]);
                    if (!$associationEntity) {
                        throw new \Exception("cannot find entity [{$colName}] of name [{$value}]");
                    }

                    if (!$entity->{$getter}() || $entity->{$getter}()->getId() !== $associationEntity->getId()) {
                        $changed = true;
                        $output[] = "<p>association [{$colName}] changed</p>";
                        $entity->{$setter}($associationEntity);
                    }
                } else {
                    if (in_array($colName, $fieldNames)) {
                        $type = $metaData->getTypeOfField((string) $colName);
                        if ('boolean' === $type) {
                            $value = (bool) $value;
                        }

                        if ($entity->{$getter}() != $value || $entity->{$getter}() === null && $entity->{$getter}() !== $value) {
                            $changed = true;
                            $output[] = "<p>field [{$colName}] changed</p>";
                            $entity->{$setter}($value);
                        }
                    }
                }
            }

            if ($changed) {
                $this->entityManager->persist($entity);
                ++$counter;
                echo implode('', $output);
            }
        }

        $this->entityManager->flush();

        return new Response($counter.' cards changed or added');
    }
}
