<?php

namespace Diesellaptops\DieselThrottling\Tests;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Illuminate\Support\Facades\Route;

class RateLimiterRegistrationTest extends TestCase
{
    #[Test]
    public function it_registers_the_named_rate_limiter(): void
    {
        $limiter = RateLimiter::limiter('api');

        $this->assertNotNull($limiter, 'Expected RateLimiter "api" to be registered.');
    }

    #[Test]
    public function it_uses_authorization_header_as_key_or_falls_back_to_ip(): void
    {
        $limiter = RateLimiter::limiter('api');
        $this->assertNotNull($limiter);

        $r1 = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '1.2.3.4']);
        $r1->headers->set('Authorization', 'Bearer abc');

        $limit1 = $limiter($r1);

        $this->assertSame(md5('Bearer abc'), $limit1->key);

        $r2 = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '9.9.9.9']);

        $limit2 = $limiter($r2);

        $this->assertSame('9.9.9.9', $limit2->key);
    }

    #[Test]
    public function it_applies_the_configured_per_minute_limit(): void
    {
        $limiter = RateLimiter::limiter('api');
        $this->assertNotNull($limiter);

        $r = Request::create('/test', 'GET', [], [], [], ['REMOTE_ADDR' => '1.1.1.1']);
        $limit = $limiter($r);

        $this->assertSame(3, $limit->maxAttempts);
        $this->assertSame(60, $limit->decaySeconds);
    }

    #[Test]
    public function it_returns_429_after_three_requests(): void
    {
        Route::middleware('throttle:api')->get('/test-throttle', function () {
            return response('ok', 200);
        });

        $this->get('/test-throttle')->assertStatus(200);
        $this->get('/test-throttle')->assertStatus(200);
        $this->get('/test-throttle')->assertStatus(200);

        $this->get('/test-throttle')->assertStatus(429);
    }
}
