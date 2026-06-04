<?php

declare(strict_types=1);

namespace App\Share\Services\Program;

use App\Share\Enums\Plan;
use App\Share\Models\Program;
use App\Share\Models\Subscription;
use App\Share\Models\SubscriptionProgramSelection;
use App\Share\Models\User;
use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProgramSelectionService
{
    public function __construct(private readonly SubscriptionService $subscriptionService) {}

    public function getStatus(User $user): ?array
    {
        $subscription = $user->validSubscription;
        if ($subscription === null) {
            return null;
        }

        return $this->buildStatusPayload($subscription);
    }

    /**
     * Selected programs for the given subscription.
     * Returns null for All Access plans (no selection needed).
     *
     * @return ?list<array<string, mixed>>
     */
    public function getSelectedPrograms(Subscription $subscription): ?array
    {
        $limits = $this->subscriptionService->getPlanLimits($subscription->plan);

        if (! $limits['requires_selection']) {
            return null;
        }

        $subscription->loadMissing([
            'programSelections' => fn ($query) => $query->with(['program' => fn ($q) => $q->withTranslation()]),
        ]);

        return $this->mapSelectedPrograms($subscription->programSelections);
    }

    /**
     * @param  list<int>  $programIds
     */
    public function syncSelection(User $user, array $programIds): array
    {
        $subscription = $user->validSubscription;

        $limits = $this->subscriptionService->getPlanLimits($subscription->plan);

        if (! $limits['requires_selection']) {
            throw ValidationException::withMessages([
                'program_ids' => [__('messages.program_selection_not_required')],
            ]);
        }

        $this->assertProgramIdsValid($subscription->plan, $programIds);

        $this->replaceSelections($subscription, $user, $programIds);

        $subscription->unsetRelation('programSelections');

        return $this->buildStatusPayload($subscription->fresh(['programSelections.program']));
    }

    /**
     * Admin: đồng bộ bộ môn theo subscription (không phụ thuộc validSubscription).
     *
     * @param  list<int>  $programIds
     */
    public function adminSyncSelections(Subscription $subscription, User $user, string $plan, array $programIds): void
    {
        $limits = $this->subscriptionService->getPlanLimits(Plan::fromValue($plan));

        if (! $limits['requires_selection']) {
            $this->clearSelections($subscription);

            return;
        }

        $this->assertProgramIdsValid($plan, $programIds);

        $this->replaceSelections($subscription, $user, $programIds);
    }

    public function clearSelections(Subscription $subscription): void
    {
        SubscriptionProgramSelection::query()
            ->where('subscription_id', $subscription->id)
            ->delete();

        $subscription->unsetRelation('programSelections');
    }

    /**
     * @param  list<int>  $programIds
     */
    private function replaceSelections(Subscription $subscription, User $user, array $programIds): void
    {
        $programIds = array_values(array_unique($programIds));

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
    }

    /**
     * @param  list<int>  $programIds
     */
    private function assertProgramIdsValid(Plan|string $plan, array $programIds): void
    {
        $planEnum = $plan instanceof Plan ? $plan : Plan::fromValue($plan);
        $limits = $this->subscriptionService->getPlanLimits($planEnum);
        $programIds = array_values(array_unique($programIds));
        $max = (int) $limits['max_programs'];

        if ($programIds === [] || count($programIds) > $max) {
            throw ValidationException::withMessages([
                'program_ids' => [__('messages.program_selection_invalid_count', ['max' => $max])],
            ]);
        }

        $existingCount = Program::query()->whereIn('id', $programIds)->count();
        if ($existingCount !== count($programIds)) {
            throw ValidationException::withMessages([
                'program_ids' => [__('messages.program_not_found')],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function buildStatusPayload(Subscription $subscription): array
    {
        $limits = $this->subscriptionService->getPlanLimits($subscription->plan);

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
}
