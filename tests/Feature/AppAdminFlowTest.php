<?php

namespace Tests\Feature;

use App\Share\Enums\Role;
use App\Share\Models\AppleSubscription;
use App\Share\Models\GoogleSubscription;
use App\Share\Models\Program;
use App\Share\Models\Subscription;
use App\Share\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class AppAdminFlowTest extends TestCase
{
    use RefreshDatabase;

    private function loginAs(string $role = Role::Admin): User
    {
        $user = User::factory()->create(['role' => $role]);
        $this->withToken(auth('api')->login($user));

        return $user;
    }

    private function file(string $path): array
    {
        return ['path' => $path, 'name' => basename($path), 'extension' => pathinfo($path, PATHINFO_EXTENSION), 'size' => 100];
    }

    private function program(): Program
    {
        $program = new Program(['rating' => 4.5]);
        $program->fill(['vi' => ['name' => 'Yoga', 'cover' => $this->file('program/cover/yoga.jpg'), 'sort' => 0]]);
        $program->save();

        return $program;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $disk = Mockery::mock(FilesystemAdapter::class);
        $disk->shouldReceive('temporaryUrl')->andReturn('https://example.test/media');
        Storage::shouldReceive('disk')->with('s3')->andReturn($disk);
    }

    public function test_all_app_admin_routes_deny_guests_and_non_admin_roles(): void
    {
        $this->get('/api/v1/admin/app/programs')->assertUnauthorized();
        $this->get('/admin')->assertNotFound();
        $this->get('/admin/login')->assertNotFound();
        $paths = ['programs', 'banners', 'combos', 'users', 'users/export'];
        foreach ($paths as $path) {
            $this->getJson('/api/v1/admin/app/'.$path)->assertUnauthorized();
        }
        foreach ([Role::Member, Role::Receptionist, Role::Trainer] as $role) {
            $this->loginAs($role);
            foreach ($paths as $path) {
                $this->getJson('/api/v1/admin/app/'.$path)->assertForbidden();
            }
            $this->postJson('/api/v1/admin/app/files/presigned-upload', [])->assertForbidden();
            $this->putJson('/api/v1/admin/app/users/1/subscription', [])->assertForbidden();
        }
    }

    public function test_register_tracks_web_app_and_rejects_spoofed_staff_source(): void
    {
        $body = ['email' => 'web@example.test', 'first_name' => 'Web', 'last_name' => 'User', 'password' => 'password123', 'registration_source' => 'web', 'created_by' => 999];
        $this->postJson('/api/v1/auth/register', $body)->assertCreated();
        $web = User::query()->where('email', $body['email'])->firstOrFail();
        $this->assertSame('web', $web->registration_source->value);
        $this->assertNull($web->created_by);
        unset($body['registration_source']);
        $body['email'] = 'app@example.test';
        $this->postJson('/api/v1/auth/register', $body)->assertCreated();
        $this->assertSame('app', User::query()->where('email', $body['email'])->firstOrFail()->registration_source->value);
        $body['email'] = 'spoof@example.test';
        $body['registration_source'] = 'admin';
        $this->postJson('/api/v1/auth/register', $body)->assertUnprocessable()->assertJsonValidationErrors('registration_source');
    }

    public function test_staff_creation_both_paths_record_actor_and_immutable_role_snapshot(): void
    {
        foreach ([Role::Admin, Role::Receptionist, Role::Trainer] as $role) {
            $actor = $this->loginAs($role);
            foreach (['users', 'members'] as $endpoint) {
                $body = ['email' => $role.$endpoint.'@example.test', 'first_name' => 'New', 'last_name' => 'Member', 'password' => 'password123', 'phone' => '0901234567', 'role' => 'member', 'registration_source' => 'web', 'created_by' => 999];
                $response = $this->postJson('/api/v1/admin/'.$endpoint, $body)->assertCreated()
                    ->assertJsonPath('data.registration_source', $role)->assertJsonPath('data.created_by.id', $actor->id);
                $created = User::query()->findOrFail($response->json('data.id'));
                $actor->update(['role' => Role::Member]);
                $this->assertSame($role, $created->fresh()->registrationProvenance()['created_by']['role']->value);
                $actor->update(['role' => $role]);
            }
        }
    }

    public function test_program_lesson_roundtrip_scoping_and_video_preservation(): void
    {
        $this->loginAs();
        $program = $this->program();
        $other = $this->program();
        $this->getJson('/api/v1/admin/app/programs')->assertOk()->assertJsonPath('data.0.translations.vi.name', 'Yoga');
        $this->putJson('/api/v1/admin/app/programs/'.$program->id, ['rating' => 4, 'translations' => ['vi' => ['name' => 'Yoga sửa', 'cover' => $this->file('program/cover/new.jpg')]]])->assertOk();
        $body = ['type' => 'special', 'level' => 'advanced', 'day' => 1, 'translations' => ['vi' => ['name' => 'Bài học', 'thumbnail' => $this->file('lesson/thumbnail/one.jpg')]], 'video' => ['file' => $this->file('lesson/video/one.mp4'), 'duration_seconds' => 120]];
        $response = $this->postJson('/api/v1/admin/app/programs/'.$program->id.'/lessons', $body)->assertCreated()->assertJsonPath('data.level', null);
        $id = $response->json('data.id');
        $this->getJson('/api/v1/admin/app/programs/'.$other->id.'/lessons/'.$id)->assertNotFound();
        $this->deleteJson('/api/v1/admin/app/programs/'.$other->id.'/lessons/'.$id)->assertNotFound();
        unset($body['video']['file']);
        $body['video']['duration_seconds'] = 180;
        $this->putJson('/api/v1/admin/app/programs/'.$program->id.'/lessons/'.$id, $body)->assertOk();
        $this->getJson('/api/v1/admin/app/programs/'.$program->id)->assertOk()
            ->assertJsonPath('data.lessons.0.video.file.path', 'lesson/video/one.mp4')
            ->assertJsonPath('data.duration_seconds', 180);
        $body['translations']['vi']['thumbnail']['path'] = 'program/cover/wrong.jpg';
        $this->putJson('/api/v1/admin/app/programs/'.$program->id.'/lessons/'.$id, $body)->assertUnprocessable();
        $this->deleteJson('/api/v1/admin/app/programs/'.$program->id.'/lessons/'.$id)->assertOk();
        $this->assertDatabaseMissing('lessons', ['id' => $id]);
    }

    public function test_banner_and_combo_multilingual_crud(): void
    {
        $this->loginAs();
        $body = ['description' => 'Sale', 'translations' => ['vi' => ['image' => $this->file('banner/image/vi.jpg'), 'link_url' => '/sale', 'order' => 1, 'is_active' => true], 'en' => ['image' => $this->file('banner/image/en.jpg'), 'order' => 2, 'is_active' => false]]];
        $banner = $this->postJson('/api/v1/admin/app/banners', $body)->assertCreated()->json('data.id');
        $this->getJson('/api/v1/admin/app/banners/'.$banner)->assertOk()->assertJsonPath('data.translations.en.is_active', false);
        $this->getJson('/api/v1/admin/app/banners')->assertOk()->assertJsonPath('data.total', 1);
        $this->putJson('/api/v1/admin/app/banners/'.$banner, $body)->assertOk();
        $this->deleteJson('/api/v1/admin/app/banners/'.$banner)->assertOk();
        $body = ['translations' => ['vi' => ['name' => 'Combo Việt', 'cover' => $this->file('combo/cover/vi.jpg')], 'en' => ['name' => 'English Combo', 'cover' => $this->file('combo/cover/en.jpg')]], 'program_ids' => [$this->program()->id, $this->program()->id], 'infos' => [['icon' => $this->file('combo/info-icon/one.png'), 'translations' => ['vi' => ['text' => 'Mỗi ngày'], 'en' => ['text' => 'Every day']]]]];
        $combo = $this->postJson('/api/v1/admin/app/combos', $body)->assertCreated()->json('data.id');
        $this->getJson('/api/v1/admin/app/combos/'.$combo)->assertOk()->assertJsonPath('data.infos.0.translations.en.text', 'Every day');
        $body['program_ids'] = array_reverse($body['program_ids']);
        $this->putJson('/api/v1/admin/app/combos/'.$combo, $body)->assertOk();
        $this->getJson('/api/v1/admin/app/combos')->assertOk()->assertJsonPath('data.items.0.program_ids', $body['program_ids']);
        $this->deleteJson('/api/v1/admin/app/combos/'.$combo)->assertOk();
    }

    public function test_subscription_invalid_selection_rolls_back_and_valid_selection_persists(): void
    {
        $this->loginAs();
        $user = User::factory()->create();
        $program = $this->program();
        $url = '/api/v1/admin/app/users/'.$user->id.'/subscription';
        $this->putJson($url, ['plan' => 'basic', 'status' => 'active', 'program_ids' => [$program->id, $this->program()->id]])->assertUnprocessable();
        $this->assertDatabaseMissing('subscriptions', ['user_id' => $user->id]);
        $this->putJson($url, ['plan' => 'basic', 'status' => 'active', 'program_ids' => [$program->id], 'auto_renew' => false])->assertOk()->assertJsonPath('data.subscription.provider', 'admin')->assertJsonPath('data.subscription.program_ids', [$program->id]);
        $this->putJson($url, ['plan' => 'all', 'status' => 'active', 'program_ids' => []])->assertOk()->assertJsonPath('data.subscription.program_ids', []);
    }

    public function test_iap_history_and_subscription_edit_preserve_provider_and_billing_fields(): void
    {
        $this->loginAs();
        $user = User::factory()->create();
        $subscription = Subscription::query()->create(['user_id' => $user->id, 'plan' => 'basic', 'provider' => 'google_iap', 'status' => 'active', 'amount' => 123, 'currency' => 'USD', 'billing_cycle' => 'monthly', 'auto_renew' => true, 'cancelled_at' => '2026-10-31 12:00:00']);
        GoogleSubscription::query()->create(['subscription_id' => $subscription->id, 'user_id' => $user->id, 'purchase_token' => 'secret-token', 'order_id' => 'google-order', 'item_id' => 'basic', 'status' => 'active', 'raw_response' => ['secret' => true], 'created_at' => '2026-10-30 12:00:00'])->forceFill(['created_at' => '2026-10-30 12:00:00'])->save();
        AppleSubscription::query()->create(['subscription_id' => $subscription->id, 'user_id' => $user->id, 'original_transaction_id' => 'apple-original', 'transaction_id' => 'apple-transaction', 'product_id' => 'plus', 'status' => 'active', 'raw_response' => ['secret' => true], 'created_at' => '2026-11-01 12:00:00'])->forceFill(['created_at' => '2026-11-01 12:00:00'])->save();
        $response = $this->getJson('/api/v1/admin/app/users/'.$user->id)->assertOk()
            ->assertJsonPath('data.transactions.0.provider', 'Apple IAP')
            ->assertJsonPath('data.transactions.1.transaction_id', 'google-order')
            ->assertJsonPath('data.subscription.billing_cycle', 'monthly');
        $this->assertStringNotContainsString('secret-token', $response->getContent());
        $this->assertStringNotContainsString('raw_response', $response->getContent());
        $this->putJson('/api/v1/admin/app/users/'.$user->id.'/subscription', ['plan' => 'all', 'status' => 'refunded', 'program_ids' => []])->assertOk()
            ->assertJsonPath('data.subscription.provider', 'google_iap')
            ->assertJsonPath('data.subscription.amount', '123.00')
            ->assertJsonPath('data.subscription.billing_cycle', 'monthly');
        $this->assertEquals($subscription->cancelled_at, $subscription->fresh()->cancelled_at);
    }

    public function test_upload_validates_media_type_size_and_returns_presigned_file_metadata(): void
    {
        $this->loginAs();
        Storage::disk('s3')->shouldReceive('temporaryUploadUrl')->once()->andReturn(['url' => 'https://example.test/upload', 'headers' => ['Content-Type' => 'image/png']]);
        $url = '/api/v1/admin/app/files/presigned-upload';
        $this->postJson($url, ['type' => 'banner_image', 'filename' => 'test.png', 'mimetype' => 'image/png', 'size' => 100])->assertOk()
            ->assertJsonPath('data.upload_url', 'https://example.test/upload')
            ->assertJsonPath('data.method', 'PUT')
            ->assertJsonPath('data.file.extension', 'png');
        $this->postJson($url, ['type' => 'banner_image', 'filename' => 'test.php', 'mimetype' => 'application/x-php', 'size' => 100])->assertUnprocessable();
        $this->postJson($url, ['type' => 'banner_image', 'filename' => 'test.png', 'mimetype' => 'image/png', 'size' => 6000000])->assertUnprocessable();
    }

    public function test_user_source_filters_csv_and_self_delete_protection(): void
    {
        $actor = $this->loginAs();
        $user = User::factory()->create(['registration_source' => 'web', 'first_name' => '=unsafe', 'last_name' => 'Export']);
        $this->getJson('/api/v1/admin/app/users?registration_source=web')->assertOk()->assertJsonPath('data.total', 1)->assertJsonPath('data.items.0.id', $user->id);
        $csv = $this->get('/api/v1/admin/app/users/export?registration_source=web')->assertOk();
        $this->assertStringContainsString("'=unsafe", $csv->getContent());
        $this->deleteJson('/api/v1/admin/app/users/'.$actor->id)->assertUnprocessable();
        $this->deleteJson('/api/v1/admin/app/users/'.$user->id)->assertOk();
        $this->assertSoftDeleted('users', ['id' => $user->id]);
    }
}
