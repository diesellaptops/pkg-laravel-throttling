<?php

namespace Diesellaptops;


use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

class DieselThrottlingProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/throttling.php', 'rate-limits');
    }

    public function boot(ConfigRepository $config): void
    {
        $limiterName = $config->get('rate-limits.limiter_name', 'api');

        RateLimiter::for($limiterName, function (Request $request) use ($config) {
            $auth = $request->header('Authorization');
            $key  = $auth ? md5($auth) : $request->ip();
            $perMinute = (int) $config->get('rate-limits.api_per_minute', 60);
            return Limit::perMinute($perMinute)->by($key);
        });
    }
}