<?php

namespace Diesellaptops\DieselThrottling\Tests;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;

class ClientIdSkipTest extends TestCase
{
    #[Test]
    public function it_returns_no_limit_when_x_client_id_is_present(): void
    {
        Route::middleware('throttle:diesel-api')->get('/test-client-id', function () {
            return response('ok', 200);
        });

        for ($i = 0; $i < 5; $i++) {
            $this->get('/test-client-id', ['x-client-id' => 'client-abc'])->assertStatus(200);
        }
    }

    #[Test]
    public function it_throttles_at_cap_when_x_client_id_is_empty(): void
    {
        Route::middleware('throttle:diesel-api')->get('/test-empty-client-id', function () {
            return response('ok', 200);
        });

        $this->get('/test-empty-client-id', ['x-client-id' => ''])->assertStatus(200);
        $this->get('/test-empty-client-id', ['x-client-id' => ''])->assertStatus(200);
        $this->get('/test-empty-client-id', ['x-client-id' => ''])->assertStatus(200);

        $this->get('/test-empty-client-id', ['x-client-id' => ''])->assertStatus(429);
    }

    #[Test]
    public function limiter_remains_registered_regardless_of_x_client_id(): void
    {
        $this->assertNotNull(RateLimiter::limiter('diesel-api'));
    }
}
