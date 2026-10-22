<?php

declare(strict_types=1);

namespace App\Controller\UserProfile;

use App\Repository\SphereRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EditProfileController extends AbstractController
{
    /**
     * @Route("/user/profile_edit", name="user_profile_edit", methods={"GET"})
     */
    public function __invoke(SphereRepository $sphereRepository): Response
    {
        $user = $this->getUser();
        $spheres = $sphereRepository->findAll();

        return $this->render('User/profile_edit.html.twig', ['user' => $user, 'spheres' => $spheres]);
    }
}
