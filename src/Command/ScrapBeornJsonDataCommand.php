<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Card;
use App\Entity\Pack;
use App\Repository\CardPrintingRepository;
use App\Repository\PackRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\VarDumper\VarDumper;

class ScrapBeornJsonDataCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CardPrintingRepository $cardPrintingRepository,
        private readonly PackRepository $packRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:beorn:json')
             ->setDescription('Download new card data from Hall of Beorn JSON Export')
             ->addOption(
                 'skip',
                 null,
                 InputOption::VALUE_REQUIRED,
                 'Number of cards to skip'
             );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getHelper('question');

        $skip = (int) $input->getOption('skip');

        if (file_exists('beorn.json')) {
            VarDumper::dump('Loading Local Beorn JSON');
            $json = file_get_contents('beorn.json');
        } else {
            VarDumper::dump('Loading Remote Beorn JSON');
            $json = file_get_contents('http://hallofbeorn.com/Export/Cards');
            file_put_contents('beorn.json', $json);
        }

        $beorn = json_decode((string) $json);

        $i = 0;
        foreach ($beorn as $data) {
            if ($skip > $i++) {
                continue;
            }

            VarDumper::dump($data->Title." $i");

            $cardset = $data->CardSet;
            $cardset = str_replace('The Hobbit: ', '', $cardset);

            /* @var $pack Pack */
            $pack = $this->packRepository->findOneBy(['name' => $cardset]);

            if (!$pack) {
                VarDumper::dump('Could not find pack '.$data->CardSet);
                continue;
            }

            /* @var $card Card */
            $bjPrinting = $this->cardPrintingRepository->createQueryBuilder('cp')
                ->join('cp.card', 'c')->where('c.name = :n')->andWhere('cp.pack = :p')
                ->setParameter('n', $data->Title)->setParameter('p', $pack)->setMaxResults(1)->getQuery()->getOneOrNullResult();
            $card = $bjPrinting ? $bjPrinting->getCard() : null;

            if (!$card) {
                if ('Hero' == $data->CardType || 'Ally' == $data->CardType || 'Attachment' == $data->CardType || 'Event' == $data->CardType) {
                    VarDumper::dump('Could not find card '.$data->Title);
                    $question = new ConfirmationQuestion('Continue?');
                    $questionHelper->ask($input, $output, $question);
                }

                continue;
            }

            if ($card->getHasErrata() != $data->HasErrata) {
                VarDumper::dump('Errata Mismatch '.$data->Title);
                VarDumper::dump($card->getHasErrata());
                VarDumper::dump($data->HasErrata);

                $question = new ConfirmationQuestion('Continue?');
                $questionHelper->ask($input, $output, $question);
            }

            if (property_exists($data->Front->Stats, 'ThreatCost') && $card->getThreat() != $data->Front->Stats->ThreatCost) {
                VarDumper::dump('Threat Mismatch '.$data->Title);
                VarDumper::dump($card->getThreat());
                VarDumper::dump($data->Front->Stats->ThreatCost);

                $question = new ConfirmationQuestion('Continue?');
                $questionHelper->ask($input, $output, $question);
            }

            if (property_exists($data->Front->Stats, 'Willpower') && $card->getWillpower() != $data->Front->Stats->Willpower) {
                VarDumper::dump('Willpower Mismatch '.$data->Title);
                VarDumper::dump($card->getWillpower());
                VarDumper::dump($data->Front->Stats->Willpower);

                $question = new ConfirmationQuestion('Continue?');
                $questionHelper->ask($input, $output, $question);
            }

            if (property_exists($data->Front->Stats, 'Attack') && $card->getAttack() != $data->Front->Stats->Attack) {
                VarDumper::dump('Attack Mismatch '.$data->Title);
                VarDumper::dump($card->getAttack());
                VarDumper::dump($data->Front->Stats->Attack);

                $question = new ConfirmationQuestion('Continue?');
                $questionHelper->ask($input, $output, $question);
            }

            if (property_exists($data->Front->Stats, 'Defense') && $card->getDefense() != $data->Front->Stats->Defense) {
                VarDumper::dump('Defense Mismatch '.$data->Title);
                VarDumper::dump($card->getDefense());
                VarDumper::dump($data->Front->Stats->Defense);

                $question = new ConfirmationQuestion('Continue?');
                $questionHelper->ask($input, $output, $question);
            }

            if (property_exists($data->Front->Stats, 'HitPoints') && $card->getHealth() != $data->Front->Stats->HitPoints) {
                VarDumper::dump('HitPoints Mismatch '.$data->Title);
                VarDumper::dump($card->getHealth());
                VarDumper::dump($data->Front->Stats->HitPoints);

                $question = new ConfirmationQuestion('Continue?');
                $questionHelper->ask($input, $output, $question);
            }

            // $card->setIllustrator($data->Artist);
        }

        $this->entityManager->flush();
        $output->writeln('Done.');

        return 0;
    }
}
