<?php

use Symfony\Component\HttpFoundation\Request;

// Maintenance mode, set by deploy.sh during the update: answered before loading anything (code,
// dependencies and database may be halfway updated).
if (file_exists(__DIR__.'/../maintenance.flag')) {
    http_response_code(503);
    header('Retry-After: 300');
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    readfile(__DIR__.'/maintenance.html');
    exit;
}

require_once __DIR__.'/../app/autoload.php';

require_once __DIR__.'/../app/AppKernel.php';
//require_once __DIR__.'/../app/AppCache.php';

$kernel = new AppKernel('prod', false);
//$kernel = new AppCache($kernel);

// When using the HttpCache, you need to call the method in your front controller instead of relying on the configuration parameter
//Request::enableHttpMethodParameterOverride();
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
