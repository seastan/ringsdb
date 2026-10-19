<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class AdminController extends AbstractController {
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction() {
        return $this->render('Admin/index.html.twig');
    }
}
