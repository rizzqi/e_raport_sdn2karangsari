<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfilTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_guests_cannot_access_profile_page(): void
    {
        $response = $this->get(route('profil.edit'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $wali = User::where('role', 'wali')->firstOrFail();

        $response = $this->actingAs($wali)->get(route('profil.edit'));
        $response->assertStatus(200);
        $response->assertSee($wali->name);
        $response->assertSee($wali->email);
        $response->assertSee('Ubah Kata Sandi');
    }

    public function test_user_can_update_their_name(): void
    {
        $user = User::where('role', 'wali')->firstOrFail();

        $response = $this->actingAs($user)->put(route('profil.update'), [
            'name' => 'Nama Baru Pendidik',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru Pendidik',
        ]);
    }

    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $user = User::where('role', 'wali')->firstOrFail();

        $response = $this->actingAs($user)->put(route('profil.password.update'), [
            'current_password' => 'password',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        $response->assertSessionHas('success');

        $user->refresh();
        $this->assertTrue(Hash::check('secret12345', $user->password));
    }

    public function test_password_change_fails_with_wrong_current_password(): void
    {
        $user = User::where('role', 'wali')->firstOrFail();

        $response = $this->actingAs($user)->put(route('profil.password.update'), [
            'current_password' => 'wrong-pass',
            'password' => 'secret12345',
            'password_confirmation' => 'secret12345',
        ]);

        $response->assertSessionHasErrors('current_password');

        $user->refresh();
        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_password_change_fails_when_confirmation_does_not_match(): void
    {
        $user = User::where('role', 'wali')->firstOrFail();

        $response = $this->actingAs($user)->put(route('profil.password.update'), [
            'current_password' => 'password',
            'password' => 'secret12345',
            'password_confirmation' => 'different54321',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
