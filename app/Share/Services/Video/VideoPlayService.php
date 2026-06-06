<?php

declare(strict_types=1);

namespace App\Share\Services\Video;

use App\Share\Attributes\File;
use App\Share\Enums\LessonType;
use App\Share\Enums\Plan;
use App\Share\Models\User;
use App\Share\Models\Video;
use App\Share\Services\Subscription\SubscriptionService;

readonly class VideoPlayService
{
    public function __construct(
        private SubscriptionService $subscriptionService,
    ) {}

    /**
     * @return array{status: int, message: string}|null
     */
    public function streamGate(User $user, Video $video): ?array
    {
        $video->loadMissing(['lesson.program']);

        $subscription = $user->validSubscription;
        if ($subscription === null) {
            return [
                'status' => 403,
                'message' => __('messages.no_active_subscription'),
            ];
        }

        $lesson = $video->lesson;
        $limits = $this->subscriptionService->getPlanLimits($subscription->plan);

        $allowedTypes = collect($limits['allowed_lesson_types']);
        if (! $allowedTypes->contains(fn (LessonType $type) => $lesson->type->is($type))) {
            return [
                'status' => 403,
                'message' => __('messages.video_lesson_type_not_allowed'),
            ];
        }

        if (! $subscription->plan->is(Plan::All)) {
            $subscription->loadMissing('programSelections');

            if ($subscription->programSelections->isEmpty()) {
                return [
                    'status' => 403,
                    'message' => __('messages.video_program_not_selected'),
                ];
            }

            $unlockedProgramIds = $subscription->programSelections->pluck('program_id');
            if (! $unlockedProgramIds->contains($lesson->program_id)) {
                return [
                    'status' => 403,
                    'message' => __('messages.video_access_denied'),
                ];
            }
        }

        if ($this->resolveVideoFile($video) === null) {
            return [
                'status' => 404,
                'message' => __('messages.video_file_not_available'),
            ];
        }

        return null;
    }

    private function resolveVideoFile(Video $video): ?File
    {
        if (! $video->relationLoaded('translations')) {
            $video->load('translations');
        }

        $file = $video->file;

        if ($file === null || $file->path === '') {
            return null;
        }

        return $file;
    }
}
