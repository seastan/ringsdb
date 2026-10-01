<?php

use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

// Code coverage: tell Xdebug (2.6+) to only collect the lines of src/. PHPUnit 6.5 does not set
// this filter itself, so Xdebug would instrument vendor/ too (Symfony, Doctrine, Twig...), which
// makes coverage runs very slow. Harmless when coverage is not collected.
if (function_exists('xdebug_set_filter')) {
    xdebug_set_filter(XDEBUG_FILTER_CODE_COVERAGE, XDEBUG_PATH_WHITELIST, [__DIR__.'/src/']);
}

if (file_exists(dirname(__DIR__).'/config/bootstrap.php')) {
    require dirname(__DIR__).'/config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
