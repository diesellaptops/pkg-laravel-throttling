<?php

namespace Diesellaptops\DieselThrottling;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class DieselThrottlingProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/diesel-throttling.php', 'diesel-throttling');
    }

    public function boot(ConfigRepository $config): void
    {
        $this->publishes(
            [
                __DIR__ . '/../config/diesel-throttling.php'
                => $this->app->configPath('diesel-throttling.php'),
            ],
            'diesel-throttling-config'
        );

        $limiterName = $config->get('diesel-throttling.limiter_name', 'api');
        $perMinute  = (int) $config->get('diesel-throttling.per_minute', 60);

        RateLimiter::for($limiterName, function (Request $request) use ($perMinute) {
            $auth = $request->header('Authorization');
            $key  = $auth ? md5($auth) : $request->ip();
            return Limit::perMinute($perMinute)->by($key);
        });
    }
}