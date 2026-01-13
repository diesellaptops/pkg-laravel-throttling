<?php

namespace Diesellaptops\DieselThrottling\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Diesellaptops\DieselThrottling\DieselThrottlingProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            DieselThrottlingProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('rate-limits.limiter_name', 'api');
        $app['config']->set('rate-limits.api_per_minute', 3);
        $app['config']->set('cache.default', 'array');
    }
}
