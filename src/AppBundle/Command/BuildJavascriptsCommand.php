<?php

namespace AppBundle\Command;

use AppBundle\Asset\JavascriptBundles;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Concatenates the JavaScript files of JavascriptBundles into web/js/ (no minification), in every
 * environment: to run after a change of one of these files (a Composer script, so on each
 * deployment).
 */
class BuildJavascriptsCommand extends Command {
    /**
     * @var string
     */
    private $rootDir;

    public function __construct(string $rootDir) {
        parent::__construct();
        $this->rootDir = $rootDir;
    }

    /**
     * @return void
     */
    protected function configure() {
        $this->setName('app:assets:js')
             ->setDescription('Concatenate the JavaScript files loaded by every page into web/js/');
    }

    protected function execute(InputInterface $input, OutputInterface $output) {
        $sourceDir = __DIR__ . '/../Resources/public';
        $webDir = $this->rootDir . '/../web';

        foreach (JavascriptBundles::BUNDLES as $target => $sources) {
            $content = '';
            foreach ($sources as $source) {
                $js = file_get_contents("$sourceDir/$source");
                if ($js === false) {
                    $output->writeln("<error>Cannot read $source</error>");
                    return 1;
                }
                $content .= $js . "\n";
            }
            if (!is_dir(dirname("$webDir/$target"))) {
                mkdir(dirname("$webDir/$target"), 0777, true);
            }
            file_put_contents("$webDir/$target", $content);
            $output->writeln(sprintf('%s: %d files, %d bytes', $target, count($sources), strlen($content)));
        }

        return 0;
    }
}
