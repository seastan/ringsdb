<?php

// The entity manager of the test environment, for phpstan-doctrine (doctrine.objectManagerLoader
// in phpstan.neon): the entity metadata comes from the YAML mappings.

require __DIR__.'/../../../vendor/autoload.php';

$kernel = new AppKernel('test', true);
$kernel->boot();

return $kernel->getContainer()->get('doctrine')->getManager();
