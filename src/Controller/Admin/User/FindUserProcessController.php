<?php

declare(strict_types=1);

namespace App\Controller\Admin\User;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class FindUserProcessController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route(path: '/admin/user/find_process', name: 'admin_find_user_process', methods: ['POST'])]
    public function __invoke(Request $request): RedirectResponse
    {
        $user = null;
        if ($request->request->get('username')) {
            $user = $this->userRepository->findOneBy(['username' => $request->request->get('username')]);
        } else {
            if ($request->request->get('id')) {
                $user = $this->userRepository->find($request->request->get('id'));
            }
        }

        if (!$user instanceof \App\Entity\User) {
            $this->addFlash('warning', 'Cannot find user');

            return $this->redirect($this->generateUrl('admin_find_user'));
        }

        return $this->redirect($this->generateUrl('admin_show_user', ['user_id' => $user->getId()]));
    }
}
