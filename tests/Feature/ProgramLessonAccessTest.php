<?php

namespace Tests\Feature;

use App\Share\Enums\LessonType;
use App\Share\Enums\Level;
use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionProvider;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Models\Lesson;
use App\Share\Models\Program;
use App\Share\Models\Subscription;
use App\Share\Models\User;
use App\Share\Models\Video;
use App\Share\Services\Video\VideoPlayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProgramLessonAccessTest extends TestCase
{
    use RefreshDatabase;

    public static function accessCases(): array
    {
        return [
            'no subscription' => [null, null, false, false, false],
            'basic selected' => [Plan::Basic, SubscriptionStatus::Active, true, true, false],
            'basic no selection' => [Plan::Basic, SubscriptionStatus::Active, false, false, false],
            'plus selected' => [Plan::Plus, SubscriptionStatus::Active, true, true, true],
            'plus no selection' => [Plan::Plus, SubscriptionStatus::Active, false, false, false],
            'all without selection' => [Plan::All, SubscriptionStatus::Active, false, true, true],
            'trial' => [Plan::Plus, SubscriptionStatus::Trial, true, true, true],
            'grace period' => [Plan::Plus, SubscriptionStatus::GracePeriod, true, true, true],
            'expired' => [Plan::All, SubscriptionStatus::Expired, false, false, false],
            'cancelled' => [Plan::All, SubscriptionStatus::Cancelled, false, false, false],
            'refunded' => [Plan::All, SubscriptionStatus::Refunded, false, false, false],
        ];
    }

    #[DataProvider('accessCases')]
    public function test_program_detail_reports_access_for_every_lesson_group(
        ?string $plan,
        ?string $status,
        bool $selected,
        bool $regularAccess,
        bool $signatureAccess,
    ): void {
        $user = User::factory()->create();
        $program = $this->programWithLessons();
        if ($plan !== null) {
            $subscription = Subscription::query()->create([
                'user_id' => $user->id,
                'plan' => $plan,
                'status' => $status,
                'provider' => SubscriptionProvider::Admin, 'amount' => 100,
            ]);
            if ($selected) {
                $subscription->programSelections()->create(['user_id' => $user->id, 'program_id' => $program->id]);
            }
        }
        $this->withToken(auth('api')->login($user));
        $response = $this->getJson('/api/v1/programs/'.$program->id)->assertOk();

        foreach (['level.beginner', 'level.intermediate', 'level.advanced', 'special', 'signature'] as $group) {
            $item = $response->json('data.lessons.'.$group.'.0');
            $this->assertSame($group === 'signature' ? $signatureAccess : $regularAccess, $item['can_access']);
            $this->assertArrayNotHasKey('file', $item);
            $this->assertArrayNotHasKey('url', $item);
            $this->assertArrayHasKey('progress', $item);
            $this->assertFalse($item['is_favorited']);
        }

        if ($selected) {
            $otherProgram = $this->programWithLessons();
            $this->getJson('/api/v1/programs/'.$otherProgram->id)->assertOk()
                ->assertJsonPath('data.lessons.level.beginner.0.can_access', false)
                ->assertJsonPath('data.lessons.special.0.can_access', false)
                ->assertJsonPath('data.lessons.signature.0.can_access', false);
        }
    }

    public function test_program_detail_still_requires_authentication(): void
    {
        $program = $this->programWithLessons();
        $this->getJson('/api/v1/programs/'.$program->id)->assertUnauthorized();
    }

    public function test_stream_gate_preserves_subscription_denials_and_missing_file_check(): void
    {
        $program = $this->programWithLessons();
        $lesson = $program->lessons()->where('type', LessonType::Signature)->firstOrFail();
        $video = Video::query()->create(['lesson_id' => $lesson->id]);
        $user = User::factory()->create();
        $service = app(VideoPlayService::class);
        $this->assertSame(['status' => 403, 'message' => __('messages.no_active_subscription')], $service->streamGate($user, $video));

        $subscription = Subscription::query()->create([
            'user_id' => $user->id, 'plan' => Plan::Basic, 'status' => SubscriptionStatus::Active, 'provider' => SubscriptionProvider::Admin, 'amount' => 100,
        ]);
        $subscription->programSelections()->create(['user_id' => $user->id, 'program_id' => $program->id]);
        $this->assertSame(['status' => 403, 'message' => __('messages.video_lesson_type_not_allowed')], $service->streamGate($user->fresh(), $video));

        $subscription->update(['plan' => Plan::All]);
        $this->assertNull($service->lessonGate($user->fresh(), $lesson));
        $this->assertSame(['status' => 404, 'message' => __('messages.video_file_not_available')], $service->streamGate($user->fresh(), $video));
    }

    private function programWithLessons(): Program
    {
        $locale = app()->getLocale();
        $program = Program::query()->create([$locale => ['name' => 'Yoga', 'sort' => 0]]);
        foreach ([Level::Beginner, Level::Intermediate, Level::Advanced] as $level) {
            Lesson::query()->create([
                'program_id' => $program->id, 'type' => LessonType::Level, 'level' => $level, 'day' => 1,
                $locale => ['name' => $level],
            ]);
        }
        foreach ([LessonType::Special, LessonType::Signature] as $type) {
            Lesson::query()->create([
                'program_id' => $program->id, 'type' => $type, 'day' => 1, $locale => ['name' => $type],
            ]);
        }

        return $program;
    }
}
