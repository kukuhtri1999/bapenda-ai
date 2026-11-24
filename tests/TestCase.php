<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Safety guard: never allow PHPUnit RefreshDatabase to touch non-testing databases
        $dbName = config('database.connections.' . config('database.default') . '.database');
        if ($dbName !== 'bapenda_ai_testing') {
            $this->fail("Unsafe test database detected ({$dbName}). Clear config cache before running tests.");
        }
    }
}
