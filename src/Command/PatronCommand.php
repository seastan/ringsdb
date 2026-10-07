<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PatronCommand extends Command
{
    use StringInputTrait;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setName('app:patron')
            ->setDescription('Add a donation to a user by email address or username')
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'Email address or username of user'
            )
            ->addArgument(
                'donation',
                InputArgument::OPTIONAL,
                'Amount of donation'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $email = self::stringArgument($input, 'email');
        $donation = (int) $input->getArgument('donation');

        $repo = $this->userRepository;
        $user = $repo->findOneBy(['email' => $email]);

        if (!$user) {
            $user = $repo->findOneBy(['username' => $email]);
        }

        if ($user) {
            if ($donation) {
                $user->setDonation($donation + $user->getDonation());
                $this->entityManager->flush();
                $output->writeln(date('c').' Success');
            } else {
                $output->writeln(date('c').' User '.$user->getUsername().' donated '.$user->getDonation());
            }
        } else {
            $output->writeln(date('c').' '."Cannot find user [$email]");
        }

        return 0;
    }
}
