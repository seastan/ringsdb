<?php

declare(strict_types=1);

namespace App\Controller\Admin\Excel;

use App\Repository\PackRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExcelDownloadFormController extends AbstractController
{
    public function __construct(
        private readonly PackRepository $packRepository
    ) {
    }

    #[Route(path: '/admin/excel/download', name: 'excel_download_form', methods: ['GET'])]
    public function __invoke(): Response
    {
        $packs = $this->packRepository->findBy([], ['dateRelease' => 'ASC', 'name' => 'ASC']);

        return $this->render('Excel/download_form.html.twig', ['packs' => $packs]);
    }
}
