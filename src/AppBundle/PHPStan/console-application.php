<?php

// The console application of the test environment, for phpstan-symfony
// (symfony.consoleApplicationLoader in phpstan.neon): the types of the commands' helpers and
// options.

require __DIR__.'/../../../vendor/autoload.php';

return new Symfony\Bundle\FrameworkBundle\Console\Application(new AppKernel('test', true));
