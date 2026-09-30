<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use AppBundle\Entity\Review;
use AppBundle\Entity\Reviewcomment;

class PatronCommand extends Command {
    use StringInputTrait;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(EntityManagerInterface $em) {
        parent::__construct();
        $this->em = $em;
    }

    /**
     * @return void
     */
    protected function configure() {
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

    protected function execute(InputInterface $input, OutputInterface $output) {
        $email = self::stringArgument($input, 'email');
        $donation = (int) $input->getArgument('donation');

        $em = $this->em;
        $repo = $em->getRepository('AppBundle:User');
        $user = $repo->findOneBy(['email' => $email]);

        if (!$user) {
            $user = $repo->findOneBy(['username' => $email]);
        }

        if ($user) {
            if ($donation) {
                $user->setDonation($donation + $user->getDonation());
                $em->flush();
                $output->writeln(date('c') . " " . "Success");
            } else {
                $output->writeln(date('c') . " User " . $user->getUsername() . " donated " . $user->getDonation());
            }
        } else {
            $output->writeln(date('c') . " " . "Cannot find user [$email]");
        }

        return 0;
    }
}
