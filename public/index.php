<?php

declare(strict_types=1);

use App\Kernel;

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

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
