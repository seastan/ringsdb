<?php

declare(strict_types=1);

// The entity manager of the test environment, for phpstan-doctrine (doctrine.objectManagerLoader
// in phpstan.neon): the entity metadata comes from the annotations of the entities.

$_SERVER['APP_ENV'] = 'test';
require __DIR__.'/../../config/bootstrap.php';

$kernel = new App\Kernel('test', true);
$kernel->boot();
$container = $kernel->getContainer();

return $container->get('doctrine')->getManager();
