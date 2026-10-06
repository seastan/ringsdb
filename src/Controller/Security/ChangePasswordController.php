<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\CurrentUserTrait;
use App\Form\Security\ChangePasswordFormType;
use App\Security\UserPasswordUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ChangePasswordController extends AbstractController
{
    use CurrentUserTrait;

    /**
     * @Route("/profile/change-password", name="fos_user_change_password", methods={"GET", "POST"})
     */
    public function __invoke(
        Request $request,
        UserPasswordUpdater $passwordUpdater,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->currentUser();
        $form = $this->createForm(ChangePasswordFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $passwordUpdater->hashPassword($user);
            $entityManager->flush();
            $this->addFlash('success', 'The password has been changed.');

            return $this->redirectToRoute('fos_user_profile_show');
        }

        return $this->render('Security/ChangePassword/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
