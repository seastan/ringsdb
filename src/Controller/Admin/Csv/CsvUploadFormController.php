<?php

declare(strict_types=1);

namespace App\Controller\Admin\Csv;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CsvUploadFormController extends AbstractController
{
    #[Route(path: '/admin/csv/upload', name: 'csv_upload_form', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('CSV/upload_form.html.twig');
    }
}
