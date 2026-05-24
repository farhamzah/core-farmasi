<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_health_responds(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJson(['status' => 'ok']);
    }

    public function test_login_and_token_validation_work(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'password',
            'active' => true,
        ]);
        $role = Role::create(['name' => 'super-admin', 'label' => 'Super Admin', 'active' => true]);

        $user->roles()->attach($role);

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.test',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/auth/validate-token')
            ->assertOk()
            ->assertJson(['valid' => true]);

        $this->withToken($token)
            ->postJson('/api/v1/auth/validate-token')
            ->assertOk()
            ->assertJson(['valid' => true]);
    }
}
