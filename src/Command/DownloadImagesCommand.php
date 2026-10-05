<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\CardRepository;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DownloadImagesCommand extends Command
{
    /**
     * @var Packages
     */
    private $packages;

    /**
     * @var string
     */
    private $publicDir;

    /**
     * @var CardRepository
     */
    private $cardRepository;

    public function __construct(Packages $packages, string $publicDir, CardRepository $cardRepository)
    {
        parent::__construct();
        $this->packages = $packages;
        $this->publicDir = $publicDir;
        $this->cardRepository = $cardRepository;
    }

    protected function configure(): void
    {
        $this
        ->setName('app:download-images')
        ->setDescription('Download missing card images from FFG websites')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $assets_helper = $this->packages;

        $repo = $this->cardRepository;

        $publicDir = $this->publicDir;
        $output->writeln($publicDir);

        $cards = $repo->findBy([], ['code' => 'ASC']);
        foreach ($cards as $card) {
            if (!$card->getPack()) {
                continue;
            }
            $card_code = $card->getCode();
            $imageurl = $assets_helper->getUrl('bundles/cards/'.$card_code.'.png');
            $imagepath = $publicDir.preg_replace('/\?.*/', '', $imageurl);
            if (file_exists($imagepath)) {
                $output->writeln('Skip '.$card_code);
            } else {
                $cgdbfile = sprintf('GT%02d_%d.jpg', $card->getPack()->getId(), $card->getPosition());
                $cgdburl = 'http://lcg-cdn.fantasyflightgames.com/got2nd/'.$cgdbfile;

                $dirname = dirname($imagepath);
                $outputfile = $dirname.DIRECTORY_SEPARATOR.$card_code.'.jpg';

                $image = file_get_contents($cgdburl);
                if (false !== $image) {
                    file_put_contents($outputfile, $image);
                    $output->writeln("New file at $outputfile");
                } else {
                    $output->writeln("Failed at downloading $cgdburl");
                }
            }
        }

        return 0;
    }
}
