<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\Role;
use App\Share\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_user_defaults_to_member_and_cannot_choose_role(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'email' => 'new@example.com',
            'password' => 'password123',
            'first_name' => 'New',
            'last_name' => 'User',
            'role' => Role::Admin,
        ])->assertCreated();

        $this->assertSame(Role::Member, User::query()->where('email', 'new@example.com')->first()->role->value);
    }

    public function test_profile_returns_role_and_keeps_existing_fields(): void
    {
        $user = User::factory()->create(['role' => Role::Trainer]);

        $this->withToken(auth('api')->login($user))
            ->getJson('/api/v1/auth/profile')
            ->assertOk()
            ->assertJsonPath('data.role', Role::Trainer)
            ->assertJsonStructure(['data' => ['id', 'email', 'first_name', 'last_name', 'plan', 'subscription_status', 'favorited_programs']]);
    }

    public function test_has_permission_denies_by_default(): void
    {
        $admin = User::factory()->create(['role' => Role::Admin])->refresh();
        $member = User::factory()->create()->refresh();

        $this->assertTrue($admin->hasPermission('packages.manage'));
        $this->assertFalse($member->hasPermission('packages.manage'));
        $this->assertFalse($admin->hasPermission('khong.ton_tai'));
    }
}
