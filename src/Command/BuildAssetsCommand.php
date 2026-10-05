<?php

declare(strict_types=1);

namespace App\Command;

use App\Asset\AssetBundles;
use ScssPhp\ScssPhp\Compiler;
use ScssPhp\ScssPhp\OutputStyle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Builds the files of AssetBundles into public/, in every environment, without minification: the
 * JavaScript files concatenated; the stylesheets concatenated, the .scss compiled, their relative
 * url(...) rewritten for their new place. To run after a change of one of these files (a Composer
 * script, so on each deployment; "make assets" in dev).
 */
class BuildAssetsCommand extends Command
{
    private string $publicDir;

    public function __construct(string $publicDir)
    {
        parent::__construct();
        $this->publicDir = $publicDir;
    }

    protected function configure(): void
    {
        $this->setName('app:assets')
             ->setDescription('Build the JavaScript and CSS files loaded by every page into public/');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sourceDir = __DIR__.'/../Resources/public';
        $bundles = [];
        foreach (AssetBundles::JAVASCRIPTS as $target => $sources) {
            $bundles[$target] = fn (string $source, string $content): string => $content;
        }

        foreach (AssetBundles::STYLESHEETS as $target => $sources) {
            $bundles[$target] = function (string $source, string $content) use ($sourceDir, $target): string {
                if ('.scss' === substr($source, -5)) {
                    $compiler = new Compiler();
                    $compiler->setImportPaths(dirname("$sourceDir/$source"));
                    $compiler->setOutputStyle(OutputStyle::EXPANDED);
                    $content = $compiler->compileString($content, "$sourceDir/$source")->getCss();
                }

                // the files are served from public/bundles/app/ (assets:install)
                return self::rewriteUrls($content, 'bundles/app/'.$source, $target);
            };
        }

        $sources = AssetBundles::JAVASCRIPTS + AssetBundles::STYLESHEETS;
        foreach ($bundles as $target => $process) {
            $content = '';
            foreach ($sources[$target] as $source) {
                $file = file_get_contents("$sourceDir/$source");
                if (false === $file) {
                    $output->writeln("<error>Cannot read $source</error>");

                    return 1;
                }

                $content .= $process($source, $file)."\n";
            }

            $path = $this->publicDir.'/'.$target;
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }

            file_put_contents($path, $content);
            $output->writeln(sprintf('%s: %d files, %d bytes', $target, count($sources[$target]), strlen($content)));
        }

        return 0;
    }

    /**
     * The relative url(...) of a stylesheet, relative to its source, made relative to the built
     * file (both paths relative to public/). Absolute URLs, data: URIs and fragments are kept.
     */
    public static function rewriteUrls(string $css, string $source, string $target): string
    {
        return (string) preg_replace_callback('/url\(\s*([\'"]?)([^\'")]+)\1\s*\)/', function (array $m) use ($source, $target) {
            $url = $m[2];
            if (preg_match('#^([a-z][a-z0-9+.-]*:|//|/|\#)#i', $url)) {
                return $m[0];
            }

            // the path, then the query / fragment
            $cut = strcspn($url, '?#');
            $segments = [];
            foreach (explode('/', dirname($source).'/'.substr($url, 0, $cut)) as $segment) {
                if ('..' === $segment) {
                    array_pop($segments);
                } elseif ('.' !== $segment && '' !== $segment) {
                    $segments[] = $segment;
                }
            }

            $from = '.' === dirname($target) ? [] : explode('/', dirname($target));
            $common = 0;
            while ($common < count($from) && $common < count($segments) - 1 && $from[$common] === $segments[$common]) {
                ++$common;
            }

            $relative = str_repeat('../', count($from) - $common).implode('/', array_slice($segments, $common));

            return 'url('.$m[1].$relative.substr($url, $cut).$m[1].')';
        }, $css);
    }
}
