<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

// Code coverage: tell Xdebug (3.x) to only collect the lines of src/. PHPUnit 8.5 does not set
// this filter itself, so Xdebug would instrument vendor/ too (Symfony, Doctrine, Twig...), which
// makes coverage runs very slow. Harmless when coverage is not collected.
if (function_exists('xdebug_set_filter')) {
    xdebug_set_filter(XDEBUG_FILTER_CODE_COVERAGE, XDEBUG_PATH_INCLUDE, [dirname(__DIR__).'/src/']);
}

if (file_exists(__DIR__.'/../config/bootstrap.php')) {
    require __DIR__.'/../config/bootstrap.php';
} elseif (method_exists(Dotenv::class, 'bootEnv')) {
    (new Dotenv())->bootEnv(__DIR__.'/../.env');
}

if ($_SERVER['APP_DEBUG']) {
    umask(0000);
}
