<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

// Phase 2 acceptance: shared login/logout, GET /me, deactivated blocked.
class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'username' => 'test.user',
            'role' => 'admin',
            'status' => 'active',
        ], $overrides));
    }

    public function test_admin_login_ok_me_returns_role_and_logout_invalidates(): void
    {
        $this->makeUser(['username' => 'admin', 'password' => 'password']);

        $login = $this->postJson('/api/login', ['username' => 'admin', 'password' => 'password']);
        $login->assertOk()->assertJsonPath('user.role', 'admin');
        $login->assertCookie('XSRF-TOKEN'); // session cookie flow active

        $this->getJson('/api/me')->assertOk()->assertJsonPath('user.username', 'admin');

        $this->postJson('/api/logout')->assertOk();
        $this->getJson('/api/me')->assertUnauthorized(); // 401 after logout
    }

    public function test_wrong_password_rejected(): void
    {
        $this->makeUser(['username' => 'wrong.pass', 'password' => 'password']);
        $this->postJson('/api/login', ['username' => 'wrong.pass', 'password' => 'nope'])
            ->assertStatus(422);
    }

    public function test_missing_fields_rejected(): void
    {
        $this->postJson('/api/login', ['username' => 'nobody'])->assertStatus(422);
        $this->postJson('/api/login', [])->assertStatus(422);
    }

    public function test_deactivated_user_blocked_on_login_and_me(): void
    {
        $this->makeUser([
            'username' => 'deactivated.user',
            'password' => 'password',
            'status' => 'deactivated',
        ]);

        // Correct password but deactivated → 403, no session.
        $this->postJson('/api/login', ['username' => 'deactivated.user', 'password' => 'password'])
            ->assertForbidden();
        $this->getJson('/api/me')->assertUnauthorized();

        // Even an authenticated deactivated session is rejected on /me.
        $user = User::where('username', 'deactivated.user')->first();
        $this->actingAs($user)->getJson('/api/me')->assertForbidden();
    }

    public function test_guest_me_is_unauthorized(): void
    {
        $this->getJson('/api/me')->assertUnauthorized();
    }

    public function test_login_throttled_after_five_bad_attempts(): void
    {
        foreach (range(1, 5) as $i) {
            $this->postJson('/api/login', ['username' => 'throttle.user', 'password' => 'bad'])
                ->assertStatus(422);
        }
        $this->postJson('/api/login', ['username' => 'throttle.user', 'password' => 'bad'])
            ->assertStatus(429);
    }

    public function test_password_change_clears_must_change_flag(): void
    {
        $this->makeUser([
            'username' => 'Enquig.john',
            'password' => 'old-password',
            'must_change_password' => true,
        ]);

        $login = $this->postJson('/api/login', ['username' => 'Enquig.john', 'password' => 'old-password']);
        $login->assertOk()->assertJsonPath('user.must_change_password', true);

        $this->postJson('/api/password/change', [
            'current_password' => 'old-password',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])->assertOk();

        $this->assertFalse(User::where('username', 'Enquig.john')->first()->must_change_password);
    }
}
