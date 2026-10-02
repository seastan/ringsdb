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

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $email = self::stringArgument($input, 'email');
        $donation = (int) $input->getArgument('donation');

        $em = $this->em;
        $repo = $this->userRepository;
        $user = $repo->findOneBy(['email' => $email]);

        if (!$user) {
            $user = $repo->findOneBy(['username' => $email]);
        }

        if ($user) {
            if ($donation) {
                $user->setDonation($donation + $user->getDonation());
                $em->flush();
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
