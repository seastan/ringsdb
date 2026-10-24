<?php

declare(strict_types=1);

// Loads the environment variables from the .env files, then sets APP_ENV and APP_DEBUG. Required by
// public/index.php, bin/console and the PHPUnit bootstrap.
//
// Symfony 3.4 has no Dotenv::loadEnv() (4.2+): the same files, in the same order, by hand: .env,
// .env.local (not in the test environment, so that the tests do not depend on the machine),
// .env.<APP_ENV>, .env.<APP_ENV>.local. Each file overrides the previous ones; the real
// environment variables (set by the web server, the shell, phpunit.xml) override them all.

use Symfony\Component\Dotenv\Dotenv;

require __DIR__.'/../vendor/autoload.php';

$projectDir = dirname(__DIR__);
$dotenv = new Dotenv();

if (is_file("$projectDir/.env")) {
    $dotenv->load("$projectDir/.env");
}

// APP_ENV decides which files come next: the real environment, else .env, else .env.local
// (where a server sets APP_ENV=prod)
$env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? 'dev';
if ('test' !== $env && is_file("$projectDir/.env.local")) {
    $dotenv->load("$projectDir/.env.local");
    $env = $_SERVER['APP_ENV'] ?? $_ENV['APP_ENV'] ?? $env;
}

foreach ([".env.$env", ".env.$env.local"] as $file) {
    if (is_file("$projectDir/$file")) {
        $dotenv->load("$projectDir/$file");
    }
}

$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $env;
$debug = $_SERVER['APP_DEBUG'] ?? $_ENV['APP_DEBUG'] ?? ('prod' !== $env);
$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = (int) $debug || filter_var($debug, FILTER_VALIDATE_BOOLEAN) ? '1' : '0';
putenv('APP_ENV='.$_SERVER['APP_ENV']);
putenv('APP_DEBUG='.$_SERVER['APP_DEBUG']);
