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
        $app['config']->set('diesel-throttling.limiter_name', 'diesel-api');
        $app['config']->set('diesel-throttling.per_minute', 3);
        $app['config']->set('cache.default', 'array');
    }
}
