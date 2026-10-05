<?php

declare(strict_types=1);

use App\Kernel;
use Symfony\Component\Debug\Debug;
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

require __DIR__.'/../config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    Debug::enable();
}

if ($trustedProxies = $_SERVER['TRUSTED_PROXIES'] ?? false) {
    Request::setTrustedProxies(explode(',', $trustedProxies), Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO);
}

if ($trustedHosts = $_SERVER['TRUSTED_HOSTS'] ?? false) {
    Request::setTrustedHosts([$trustedHosts]);
}

$kernel = new Kernel($_SERVER['APP_ENV'], (bool) $_SERVER['APP_DEBUG']);
$request = Request::createFromGlobals();
$response = $kernel->handle($request);
$response->send();
$kernel->terminate($request, $response);
