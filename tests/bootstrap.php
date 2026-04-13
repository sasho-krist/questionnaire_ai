<?php

/**
 * Runs before PHPUnit loads config. Ensures the app never boots tests against MySQL
 * when the IDE or another runner skips phpunit.xml env merging.
 */
$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = 'testing';
putenv('APP_ENV=testing');

$testingAppKey = 'base64:Drv6YVU/6wG+5AL8JMKtlAEAQk5M/4kZcpMx3ED2xPg=';
$_SERVER['APP_KEY'] = $_ENV['APP_KEY'] = $testingAppKey;
putenv('APP_KEY='.$testingAppKey);

$_SERVER['DB_CONNECTION'] = $_ENV['DB_CONNECTION'] = 'sqlite';
putenv('DB_CONNECTION=sqlite');

$_SERVER['DB_DATABASE'] = $_ENV['DB_DATABASE'] = ':memory:';
putenv('DB_DATABASE=:memory:');

$_SERVER['DB_URL'] = $_ENV['DB_URL'] = '';
putenv('DB_URL');

require dirname(__DIR__).'/vendor/autoload.php';

/*
 * If `php artisan config:cache` was run for local/production, Laravel loads the entire
 * config from bootstrap/cache/config.php and ignores DB_* from the environment — tests
 * would then use MySQL from the cache and RefreshDatabase would wipe your real database.
 */
$basePath = dirname(__DIR__);
$configCache = $basePath.DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache'.DIRECTORY_SEPARATOR.'config.php';
if (is_file($configCache)) {
    @unlink($configCache);
}
