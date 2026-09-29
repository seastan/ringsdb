<?php

namespace AppBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteInactiveCommand extends Command {
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
            ->setName('app:inactive-users')
            ->setDescription('Delete users inactive since 48 hours');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $em = $this->em;
        $limit = new \DateTime();
        $limit->sub(new \DateInterval('PT48H'));
        $count = 0;

        $users = $em->getRepository('AppBundle:User')->findBy(array('enabled' => false));
        foreach($users as $user) {
            /* @var $user AppBundle\Entity\User */
            if ($user->getDateCreation() < $limit) {
                $count++;
                $em->remove($user);
            }
        }
        $em->flush();
        $output->writeln(date('c') . " Delete $count inactive users.");

        return 0;
    }
}
