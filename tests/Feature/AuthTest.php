<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_register_pages_render_for_guest(): void
    {
        $this->get('/login')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Auth/Login'));
        $this->get('/register')->assertOk()->assertInertia(fn (Assert $p) => $p->component('Auth/Register'));
    }

    public function test_member_logs_in_with_username_or_email(): void
    {
        $user = User::factory()->create(['username' => 'ifs24038', 'email' => 'ifs@example.com']);

        $this->post('/login', ['username' => 'ifs24038', 'password' => 'password'])
            ->assertRedirect(route('dashboard.posts.index'));
        $this->post('/logout');

        $this->post('/login', ['username' => 'ifs@example.com', 'password' => 'password'])
            ->assertRedirect(route('dashboard.posts.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_admin_is_redirected_to_admin_area(): void
    {
        User::factory()->admin()->create(['username' => 'boss']);

        $this->post('/login', ['username' => 'boss', 'password' => 'password'])
            ->assertRedirect(route('admin.users'));
    }

    public function test_wrong_credentials_are_rejected(): void
    {
        User::factory()->create(['username' => 'ifs24038']);

        $this->post('/login', ['username' => 'ifs24038', 'password' => 'salah'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_registration_creates_member_and_logs_in(): void
    {
        $this->post('/register', [
            'name' => 'Penulis Baru',
            'username' => 'penulis_baru',
            'email' => 'baru@example.com',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
        ])->assertRedirect(route('dashboard.posts.index'));

        $this->assertDatabaseHas('users', ['username' => 'penulis_baru', 'role' => 'member']);
        $this->assertAuthenticated();
    }

    public function test_guest_is_redirected_from_dashboard_and_admin(): void
    {
        $this->get('/dashboard/posts')->assertRedirect('/login');
        $this->get('/admin/users')->assertRedirect('/login');
    }
}
