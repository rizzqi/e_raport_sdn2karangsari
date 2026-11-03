<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginPageTest extends TestCase
{
    public function test_login_page_renders_branding_and_form(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee('Selamat Datang Kembali');
        $response->assertSee('name="email"', false);
        $response->assertSee('name="password"', false);
        $response->assertSee('name="_token"', false);
    }
}
