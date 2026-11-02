<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class LoginRateLimiterTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        RateLimiter::clear('admin@sdn2karangsari.sch.id|127.0.0.1');
    }

    public function test_login_throttles_after_five_failed_attempts(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login.post'), [
                'email' => 'admin@sdn2karangsari.sch.id',
                'password' => 'wrong-password',
            ]);
            $response->assertSessionHasErrors('email');
        }

        // 6th attempt should be throttled
        $throttledResponse = $this->post(route('login.post'), [
            'email' => 'admin@sdn2karangsari.sch.id',
            'password' => 'wrong-password',
        ]);

        $throttledResponse->assertSessionHasErrors('email');
        $this->assertStringContainsString('Terlalu banyak percobaan login', session('errors')->first('email'));
    }

    public function test_successful_login_clears_rate_limiter(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => 'admin@sdn2karangsari.sch.id',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticated();
    }
}
