<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class ApiLoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_five_failed_logins_block_further_attempts_without_replacing_the_existing_token(): void
    {
        $user = $this->createUser(['api_token' => str_repeat('a', 64)]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertUnauthorized()->assertJson(['message' => 'Invalid credentials']);
        }

        Hash::shouldReceive('check')->never();

        $blocked = $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertStatus(429)
            ->assertHeader('X-RateLimit-Limit', '5')
            ->assertHeader('X-RateLimit-Remaining', '0')
            ->assertJsonMissingPath('token');

        $this->assertGreaterThan(0, (int) $blocked->headers->get('Retry-After'));
        $this->assertLessThanOrEqual(60, (int) $blocked->headers->get('Retry-After'));
        $this->assertSame(str_repeat('a', 64), $user->fresh()->api_token);
        $this->assertTrue($user->fresh()->active);
    }

    public function test_a_different_account_on_the_same_ip_can_still_login_and_validate_its_token(): void
    {
        $blockedUser = $this->createUser();
        $otherUser = $this->createUser(['email' => 'other-user@example.test']);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $blockedUser->email,
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $login = $this->postJson('/api/v1/auth/login', [
            'email' => $otherUser->email,
            'password' => 'correct-password',
        ])->assertOk()->assertJsonPath('user.id', $otherUser->id);

        $this->withToken($login->json('token'))
            ->getJson('/api/v1/auth/validate-token')
            ->assertOk()->assertJsonPath('valid', true);

        $this->getJson('/api/v1/health')->assertOk();
    }

    public function test_changing_ip_does_not_bypass_the_account_limit(): void
    {
        $user = $this->createUser();

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.'.$attempt])
                ->postJson('/api/v1/auth/login', [
                    'email' => $user->email,
                    'password' => 'wrong-password',
                ])->assertUnauthorized();
        }

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'correct-password',
            ])->assertStatus(429);
    }

    public function test_email_case_and_whitespace_do_not_bypass_the_limit(): void
    {
        $user = $this->createUser();

        foreach ([
            'login-user@example.test',
            'LOGIN-USER@EXAMPLE.TEST',
            ' Login-User@example.test ',
            'login-user@EXAMPLE.TEST',
            ' login-user@example.test ',
        ] as $email) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertStatus(429);
    }

    public function test_successful_logins_do_not_consume_the_failed_attempt_budget(): void
    {
        $user = $this->createUser();

        for ($attempt = 0; $attempt < 8; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'correct-password',
            ])->assertOk()->assertJsonPath('user.id', $user->id);
        }

        for ($attempt = 0; $attempt < 4; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertOk();
    }

    public function test_the_account_can_login_again_after_the_rate_limit_expires(): void
    {
        $user = $this->createUser();

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->travel(61)->seconds();

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertOk()->assertJsonPath('user.id', $user->id);
    }

    public function test_nonexistent_accounts_receive_the_same_failed_login_limit(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => 'missing-user@example.test',
                'password' => 'wrong-password',
            ])->assertUnauthorized()->assertJson(['message' => 'Invalid credentials']);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => 'missing-user@example.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_inactive_accounts_are_rate_limited_without_changing_their_state(): void
    {
        $user = $this->createUser(['active' => false, 'api_token' => str_repeat('b', 64)]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'correct-password',
            ])->assertUnauthorized()->assertJson(['message' => 'Invalid credentials']);
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertStatus(429);

        $this->assertFalse($user->fresh()->active);
        $this->assertSame(str_repeat('b', 64), $user->fresh()->api_token);
    }

    public function test_malformed_email_payloads_are_validated_without_blocking_a_valid_account(): void
    {
        $user = $this->createUser();

        foreach ([[$user->email], null, 12345] as $email) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $email,
                'password' => 'wrong-password',
            ])->assertUnprocessable()->assertJsonValidationErrors('email');
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertOk();
    }

    public function test_the_failed_attempt_limit_is_configurable(): void
    {
        config(['core_api.login_max_failed_attempts_per_minute' => 2]);
        $user = $this->createUser();

        for ($attempt = 0; $attempt < 2; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->postJson('/api/v1/auth/login', [
            'email' => $user->email,
            'password' => 'correct-password',
        ])->assertStatus(429)->assertHeader('X-RateLimit-Limit', '2');
    }

    public function test_account_rate_limit_does_not_change_profile_portal_login(): void
    {
        $user = $this->createUser(['must_change_password' => false]);

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->postJson('/api/v1/auth/login', [
                'email' => $user->email,
                'password' => 'wrong-password',
            ])->assertUnauthorized();
        }

        $this->post('/profile/login', [
            'login' => $user->email,
            'password' => 'correct-password',
        ])->assertRedirect();

        $this->assertAuthenticatedAs($user);
    }

    public function test_rate_limit_keys_do_not_contain_plaintext_email_addresses(): void
    {
        $request = Request::create('/api/v1/auth/login', 'POST', [
            'email' => ' Login-User@Example.Test ',
        ]);
        $limit = RateLimiter::limiter('core-api-login')($request);

        $this->assertSame('account:'.hash('sha256', 'login-user@example.test'), $limit->key);
        $this->assertStringNotContainsString('login-user@example.test', $limit->key);
        $this->assertSame(5, $limit->maxAttempts);
        $this->assertSame(60, $limit->decaySeconds);
    }

    private function createUser(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'login-user@example.test',
            'password' => 'correct-password',
            'active' => true,
        ], $attributes));
    }
}
