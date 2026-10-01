<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_home_redirects_to_login(): void
    {
        $this->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_entering_home_are_sent_to_dashboard(): void
    {
        $user = new User([
            'name' => 'Usuario de prueba',
            'email' => 'usuario@example.test',
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertRedirect(route('dashboard'));
    }

    public function test_logout_requires_post_and_works_with_a_stale_session_token(): void
    {
        $user = new User([
            'name' => 'Usuario de prueba',
            'email' => 'usuario@example.test',
            'password' => 'password',
        ]);

        $this->actingAs($user)
            ->get('/logout')
            ->assertMethodNotAllowed();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

}
