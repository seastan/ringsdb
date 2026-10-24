<?php

declare(strict_types=1);

namespace App\Controller\Security;

use App\Controller\CurrentUserTrait;
use App\Form\Security\ProfileFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * The account form: username and email, confirmed by the current password.
 */
class ProfileEditController extends AbstractController
{
    use CurrentUserTrait;

    #[Route(path: '/profile/edit', name: 'fos_user_profile_edit', methods: ['GET', 'POST'])]
    public function __invoke(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ProfileFormType::class, $this->currentUser());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'The profile has been updated.');

            return $this->redirectToRoute('fos_user_profile_show');
        }

        return $this->render('Security/Profile/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
