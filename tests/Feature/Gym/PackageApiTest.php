<?php

namespace Tests\Feature\Gym;

use App\Share\Enums\PackageStatus;
use App\Share\Enums\PackageType;
use App\Share\Enums\Role;
use App\Share\Models\Package;
use App\Share\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PackageApiTest extends TestCase
{
    use RefreshDatabase;

    private function createPackage(array $overrides = []): Package
    {
        return Package::query()->create(array_merge([
            'package_type' => PackageType::ClassPack,
            'price' => 2100000,
            'duration_days' => 135,
            'class_credits' => 10,
            'session_duration_minutes' => 60,
            'vi' => ['name' => '10 classes', 'description' => 'Mô tả'],
        ], $overrides))->refresh();
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => '5 classes',
            'description' => 'Mô tả',
            'package_type' => PackageType::ClassPack,
            'price' => 1250000,
            'duration_days' => 67,
            'class_credits' => 5,
            'session_duration_minutes' => 60,
        ], $overrides);
    }

    private function actingAsRole(string $role): static
    {
        return $this->withToken(auth('api')->login(User::factory()->create(['role' => $role])));
    }

    public function test_public_list_only_returns_active_packages(): void
    {
        $active = $this->createPackage();
        $this->createPackage(['status' => PackageStatus::Hidden, 'vi' => ['name' => 'Ẩn']]);

        $this->getJson('/api/v1/packages')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id)
            ->assertJsonPath('data.0.name', '10 classes')
            ->assertJsonPath('data.0.package_type', PackageType::ClassPack)
            ->assertJsonPath('data.0.price', 2100000);
    }

    public function test_public_detail_hides_hidden_package(): void
    {
        $hidden = $this->createPackage(['status' => PackageStatus::Hidden]);

        $this->getJson("/api/v1/packages/{$hidden->id}")->assertNotFound();
        $this->getJson('/api/v1/packages/'.$this->createPackage()->id)->assertOk();
    }

    public function test_admin_endpoints_require_login(): void
    {
        $this->getJson('/api/v1/admin/packages')->assertUnauthorized();
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admin_roles_are_forbidden(string $role): void
    {
        $package = $this->createPackage();
        $client = $this->actingAsRole($role);

        $client->getJson('/api/v1/admin/packages')->assertForbidden();
        $client->postJson('/api/v1/admin/packages', $this->payload())->assertForbidden();
        $client->patchJson("/api/v1/admin/packages/{$package->id}/status", ['status' => PackageStatus::Hidden])->assertForbidden();
        $client->deleteJson("/api/v1/admin/packages/{$package->id}")->assertForbidden();

        $this->assertDatabaseHas('packages', ['id' => $package->id, 'status' => PackageStatus::Active]);
    }

    public static function nonAdminRoles(): array
    {
        return [[Role::Receptionist], [Role::Trainer], [Role::Member]];
    }

    public function test_admin_can_create_update_hide_and_delete(): void
    {
        $client = $this->actingAsRole(Role::Admin);

        $id = $client->postJson('/api/v1/admin/packages', $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.name', '5 classes')
            ->assertJsonPath('data.status', PackageStatus::Active)
            ->assertJsonPath('data.base_people_max', 2)
            ->assertJsonPath('data.extra_people_session_max', 100)
            ->json('data.id');

        $client->patchJson("/api/v1/admin/packages/{$id}", $this->payload(['name' => 'Đổi tên', 'price' => 999000]))
            ->assertOk()
            ->assertJsonPath('data.name', 'Đổi tên')
            ->assertJsonPath('data.price', 999000);

        $client->patchJson("/api/v1/admin/packages/{$id}/status", ['status' => PackageStatus::Hidden])
            ->assertOk()
            ->assertJsonPath('data.status', PackageStatus::Hidden);

        $client->getJson("/api/v1/admin/packages/{$id}")->assertOk();
        $this->getJson("/api/v1/packages/{$id}")->assertNotFound();

        $client->deleteJson("/api/v1/admin/packages/{$id}")->assertOk();
        $this->assertDatabaseMissing('packages', ['id' => $id]);
        $this->assertDatabaseMissing('package_translations', ['package_id' => $id]);
    }

    public function test_admin_list_filters_by_name_and_status(): void
    {
        $this->createPackage();
        $hidden = $this->createPackage(['status' => PackageStatus::Hidden, 'vi' => ['name' => 'Unlimited 3 tháng']]);
        $client = $this->actingAsRole(Role::Admin);

        $client->getJson('/api/v1/admin/packages?q=Unlimited')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $hidden->id);
        $client->getJson('/api/v1/admin/packages?status=hidden')->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $hidden->id);
        $client->getJson('/api/v1/admin/packages?status=sai')->assertUnprocessable();
    }

    public function test_credits_are_required_unless_unlimited(): void
    {
        $client = $this->actingAsRole(Role::Admin);

        $client->postJson('/api/v1/admin/packages', $this->payload(['class_credits' => null]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('class_credits');

        $client->postJson('/api/v1/admin/packages', $this->payload(['package_type' => PackageType::Unlimited, 'class_credits' => 10]))
            ->assertCreated()
            ->assertJsonPath('data.class_credits', null);
    }

    public function test_changing_to_unlimited_clears_credits(): void
    {
        $package = $this->createPackage();

        $this->actingAsRole(Role::Admin)
            ->patchJson("/api/v1/admin/packages/{$package->id}", $this->payload(['package_type' => PackageType::Unlimited]))
            ->assertOk()
            ->assertJsonPath('data.class_credits', null);
    }

    public function test_validation_rejects_invalid_values(): void
    {
        $this->actingAsRole(Role::Admin)
            ->postJson('/api/v1/admin/packages', $this->payload(['name' => '', 'price' => 0, 'duration_days' => 0, 'package_type' => 'sai']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'price', 'duration_days', 'package_type']);
    }

    public function test_name_is_stored_per_locale_with_fallback(): void
    {
        $package = $this->createPackage();
        $client = $this->actingAsRole(Role::Admin);

        $client->withHeader('x-locale', 'en')->getJson("/api/v1/admin/packages/{$package->id}")
            ->assertJsonPath('data.name', '10 classes');

        $client->withHeader('x-locale', 'en')
            ->patchJson("/api/v1/admin/packages/{$package->id}", $this->payload(['name' => 'Ten classes']))
            ->assertJsonPath('data.name', 'Ten classes');

        $this->assertDatabaseHas('package_translations', ['package_id' => $package->id, 'locale' => 'vi', 'name' => '10 classes']);
    }
}
