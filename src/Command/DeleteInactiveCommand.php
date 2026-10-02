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
    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * @var UserRepository
     */
    private $userRepository;

    public function __construct(EntityManagerInterface $em, UserRepository $userRepository)
    {
        parent::__construct();
        $this->em = $em;
        $this->userRepository = $userRepository;
    }

    protected function configure(): void
    {
        $this
            ->setName('app:inactive-users')
            ->setDescription('Delete users inactive since 48 hours');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->em;
        $limit = new \DateTime();
        $limit->sub(new \DateInterval('PT48H'));
        $count = 0;

        $users = $this->userRepository->findBy(['enabled' => false]);
        foreach ($users as $user) {
            /* @var $user App\Entity\User */
            if ($user->getDateCreation() < $limit) {
                ++$count;
                $em->remove($user);
            }
        }
        $em->flush();
        $output->writeln(date('c')." Delete $count inactive users.");

        return 0;
    }
}
