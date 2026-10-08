<?php

declare(strict_types=1);

namespace App\Controller\Admin\User;

use App\Model\FindUserDto;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class FindUserProcessController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository
    ) {
    }

    #[Route(path: '/admin/user/find_process', name: 'admin_find_user_process', methods: ['POST'])]
    public function __invoke(#[MapRequestPayload] FindUserDto $payload = new FindUserDto()): RedirectResponse
    {
        $user = null;
        if ($payload->username) {
            $user = $this->userRepository->findOneBy(['username' => $payload->username]);
        } else {
            if ($payload->id) {
                $user = $this->userRepository->find($payload->id);
            }
        }

        if (!$user instanceof \App\Entity\User) {
            $this->addFlash('warning', 'Cannot find user');

            return $this->redirect($this->generateUrl('admin_find_user'));
        }

        return $this->redirect($this->generateUrl('admin_show_user', ['user_id' => $user->getId()]));
    }
}
