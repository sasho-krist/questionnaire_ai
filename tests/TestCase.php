<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Fail fast before traits like RefreshDatabase run, so MySQL is never migrated or wiped.
     */
    protected function refreshApplication(): void
    {
        parent::refreshApplication();

        if (! $this->app->environment('testing')) {
            return;
        }

        $default = (string) config('database.default');
        $driver = (string) config("database.connections.{$default}.driver");

        if ($driver !== 'sqlite' || $default !== 'sqlite') {
            throw new RuntimeException(
                "Unsafe test configuration: default connection [{$default}] uses driver [{$driver}]. ".
                'Tests must use SQLite only so your development MySQL database is never modified. '.
                'Run `php artisan test` from the project root, avoid `config:cache` before tests, '.
                'and keep tests/bootstrap.php as the PHPUnit bootstrap.'
            );
        }

        $sqliteDb = config('database.connections.sqlite.database');

        if ($sqliteDb !== ':memory:') {
            throw new RuntimeException(
                'Unsafe test configuration: SQLite must use :memory: for tests (found '.
                json_encode($sqliteDb, JSON_UNESCAPED_SLASHES).
                '). Otherwise RefreshDatabase can wipe a real database file on disk.'
            );
        }
    }
}
