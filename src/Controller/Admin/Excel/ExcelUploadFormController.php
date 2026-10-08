<?php

declare(strict_types=1);

namespace App\Controller\Admin\Excel;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExcelUploadFormController extends AbstractController
{
    #[Route(path: '/admin/excel/upload', name: 'excel_upload_form', methods: ['GET'])]
    public function __invoke(): Response
    {
        return $this->render('Excel/upload_form.html.twig');
    }
}
