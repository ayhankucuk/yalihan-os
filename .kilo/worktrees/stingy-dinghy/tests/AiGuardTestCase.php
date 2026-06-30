<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;

abstract class AiGuardTestCase extends BaseTestCase
{
    use CreatesApplication;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable Database for these tests
        // We only test Logic + Cache
        Config::set('cache.default', 'array');
        Cache::flush();
    }
}
