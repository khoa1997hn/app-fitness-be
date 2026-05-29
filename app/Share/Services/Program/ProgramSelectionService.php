<?php

declare(strict_types=1);

namespace App\Share\Services\Program;

use App\Share\Enums\LessonType;
use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Models\Program;
use App\Share\Models\Subscription;
use App\Share\Models\SubscriptionProgramSelection;
use App\Share\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgramSelectionService
{
    /**
     * @return array{requires_selection: bool, max_programs: ?int, allowed_lesson_types: list<string>}
     */
    public function getPlanLimits(Plan $plan): array
    {
        if ($plan->is(Plan::All)) {
            return [
                'requires_selection' => false,
                'max_programs' => null,
                'allowed_lesson_types' => [
                    LessonType::Level,
                    LessonType::Special,
                    LessonType::Signature,
                ],
            ];
        }

        if ($plan->is(Plan::Plus)) {
            return [
                'requires_selection' => true,
                'max_programs' => 2,
                'allowed_lesson_types' => [
                    LessonType::Level,
                    LessonType::Special,
                    LessonType::Signature,
                ],
            ];
        }

        return [
            'requires_selection' => true,
            'max_programs' => 1,
            'allowed_lesson_types' => [
                LessonType::Level,
                LessonType::Special,
            ],
        ];
    }

    public function getStatus(User $user): ?array
    {
        $subscription = $user->validSubscription;
        if ($subscription === null) {
            return null;
        }

        return $this->buildStatusPayload($subscription);
    }

    /**
     * @return array{subscription: ?array<string, mixed>, programs: list<array<string, mixed>>}
     */
    public function getPurchased(User $user): array
    {
        $subscription = $user->subscription;

        if ($subscription === null) {
            return [
                'subscription' => null,
                'programs' => [],
            ];
        }

        $subscription->loadMissing([
            'programSelections' => fn ($query) => $query->with(['program' => fn ($q) => $q->withTranslation()]),
        ]);

        $limits = $this->getPlanLimits($subscription->plan);

        return [
            'subscription' => $this->mapPurchasedSubscription($subscription, $limits),
            'programs' => $this->resolvePurchasedPrograms($subscription),
        ];
    }

    public function canCancelRenewal(Subscription $subscription): bool
    {
        if ($subscription->cancelled_at !== null) {
            return false;
        }

        if (! $subscription->auto_renew) {
            return false;
        }

        return $subscription->status->in([
            SubscriptionStatus::Trial,
            SubscriptionStatus::Active,
            SubscriptionStatus::GracePeriod,
        ]);
    }

    public function canRenew(Subscription $subscription): bool
    {
        if ($subscription->cancelled_at !== null) {
            return true;
        }

        return $subscription->status->in([
            SubscriptionStatus::Cancelled,
            SubscriptionStatus::Expired,
        ]);
    }

    /**
     * @param  array{requires_selection: bool, max_programs: ?int, allowed_lesson_types: list<string>}  $limits
     * @return array<string, mixed>
     */
    private function mapPurchasedSubscription(Subscription $subscription, array $limits): array
    {
        return [
            'id' => $subscription->id,
            'plan' => $subscription->plan,
            'status' => $subscription->status,
            'provider' => $subscription->provider,
            'amount' => (float) $subscription->amount,
            'currency' => $subscription->currency,
            'auto_renew' => $subscription->auto_renew,
            'started_at' => $subscription->created_at?->toIso8601String(),
            'expires_at' => $subscription->expires_at?->toIso8601String(),
            'renews_at' => $this->resolveRenewsAt($subscription),
            'cancelled_at' => $subscription->cancelled_at?->toIso8601String(),
            'show_plan_ends_notice' => $this->shouldShowPlanEndsNotice($subscription),
            'can_cancel_renewal' => $this->canCancelRenewal($subscription),
            'can_renew' => $this->canRenew($subscription),
            'requires_selection' => $limits['requires_selection'],
            'max_programs' => $limits['max_programs'],
            'allowed_lesson_types' => $limits['allowed_lesson_types'],
        ];
    }

    private function shouldShowPlanEndsNotice(Subscription $subscription): bool
    {
        if ($subscription->auto_renew) {
            return false;
        }

        if ($subscription->expires_at === null || $subscription->expires_at->isPast()) {
            return false;
        }

        return $subscription->status->in([
            SubscriptionStatus::Trial,
            SubscriptionStatus::Active,
            SubscriptionStatus::GracePeriod,
        ]);
    }

    /**
     * @param  list<int>  $programIds
     */
    public function syncSelection(User $user, array $programIds): array
    {
        $subscription = $user->validSubscription;

        $limits = $this->getPlanLimits($subscription->plan);

        if (! $limits['requires_selection']) {
            throw ValidationException::withMessages([
                'program_ids' => [__('messages.program_selection_not_required')],
            ]);
        }

        $programIds = array_values(array_unique($programIds));

        if ($programIds === [] || count($programIds) > (int) $limits['max_programs']) {
            throw ValidationException::withMessages([
                'program_ids' => [__('messages.program_selection_invalid_count', ['max' => $limits['max_programs']])],
            ]);
        }

        $existingCount = Program::query()->whereIn('id', $programIds)->count();
        if ($existingCount !== count($programIds)) {
            throw ValidationException::withMessages([
                'program_ids' => [__('messages.program_not_found')],
            ]);
        }

        DB::transaction(function () use ($subscription, $user, $programIds): void {
            SubscriptionProgramSelection::query()
                ->where('subscription_id', $subscription->id)
                ->delete();

            foreach ($programIds as $programId) {
                SubscriptionProgramSelection::query()->create([
                    'subscription_id' => $subscription->id,
                    'user_id' => $user->id,
                    'program_id' => $programId,
                ]);
            }
        });

        $subscription->unsetRelation('programSelections');

        return $this->buildStatusPayload($subscription->fresh(['programSelections.program']));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatusPayload(Subscription $subscription): array
    {
        $limits = $this->getPlanLimits($subscription->plan);

        $subscription->loadMissing([
            'programSelections' => fn ($query) => $query->with(['program' => fn ($q) => $q->withTranslation()]),
        ]);

        return [
            'subscription_id' => $subscription->id,
            'plan' => $subscription->plan,
            'requires_selection' => $limits['requires_selection'],
            'max_programs' => $limits['max_programs'],
            'allowed_lesson_types' => $limits['allowed_lesson_types'],
            'selected_programs' => $this->mapSelectedPrograms($subscription->programSelections),
        ];
    }

    /**
     * @param  Collection<int, SubscriptionProgramSelection>  $selections
     * @return list<array<string, mixed>>
     */
    private function mapSelectedPrograms(Collection $selections): array
    {
        return $selections
            ->sortBy('created_at')
            ->values()
            ->map(fn (SubscriptionProgramSelection $selection) => [
                'id' => $selection->program_id,
                'name' => $selection->program->name,
                'selected_at' => $selection->created_at?->toIso8601String(),
            ])
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function resolvePurchasedPrograms(Subscription $subscription): array
    {
        if ($subscription->plan->is(Plan::All)) {
            return Program::query()
                ->withTranslation()
                ->orderByTranslation('sort')
                ->orderBy('id', 'desc')
                ->get()
                ->map(fn (Program $program) => [
                    'id' => $program->id,
                    'name' => $program->name,
                    'cover' => $program->cover,
                    'selected_at' => null,
                ])
                ->all();
        }

        return $subscription->programSelections
            ->sortBy('created_at')
            ->values()
            ->map(fn (SubscriptionProgramSelection $selection) => [
                'id' => $selection->program_id,
                'name' => $selection->program->name,
                'cover' => $selection->program->cover,
                'selected_at' => $selection->created_at?->toIso8601String(),
            ])
            ->all();
    }

    private function resolveRenewsAt(Subscription $subscription): ?string
    {
        if (! $subscription->auto_renew) {
            return null;
        }

        if (! $subscription->status->in([
            SubscriptionStatus::Trial,
            SubscriptionStatus::Active,
            SubscriptionStatus::GracePeriod,
        ])) {
            return null;
        }

        return $subscription->expires_at?->toIso8601String();
    }
}
