<?php

declare(strict_types=1);

// The console application of the test environment, for phpstan-symfony
// (symfony.consoleApplicationLoader in phpstan.neon): the types of the commands' helpers and
// options.

$_SERVER['APP_ENV'] = 'test';
require __DIR__.'/../../config/bootstrap.php';

return new Symfony\Bundle\FrameworkBundle\Console\Application(new App\Kernel('test', true));
