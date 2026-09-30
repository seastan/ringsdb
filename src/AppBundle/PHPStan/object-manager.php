<?php

// The entity manager of the test environment, for phpstan-doctrine (doctrine.objectManagerLoader
// in phpstan.neon): the entity metadata comes from the YAML mappings.

require __DIR__.'/../../../vendor/autoload.php';

$kernel = new AppKernel('test', true);
$kernel->boot();
$container = $kernel->getContainer();
if ($container === null) {
    throw new \LogicException('The kernel has no container.');
}

return $container->get('doctrine')->getManager();
