<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteInactiveCommand extends Command
{
    public function __construct(private EntityManagerInterface $entityManager, private UserRepository $userRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:inactive-users')
            ->setDescription('Delete users inactive since 48 hours');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $limit = new \DateTime();
        $limit->sub(new \DateInterval('PT48H'));

        $count = 0;

        $users = $this->userRepository->findBy(['enabled' => false]);
        foreach ($users as $user) {
            /* @var $user App\Entity\User */
            if ($user->getDateCreation() < $limit) {
                ++$count;
                $this->entityManager->remove($user);
            }
        }

        $this->entityManager->flush();
        $output->writeln(date('c')." Delete $count inactive users.");

        return 0;
    }
}
