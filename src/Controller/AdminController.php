<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/admin/", name="admin", methods={"GET"})
     */
    public function indexAction()
    {
        return $this->render('Admin/index.html.twig');
    }
}
