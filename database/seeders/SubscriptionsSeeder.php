<?php

namespace Database\Seeders;

use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Models\Program;
use App\Share\Models\User;
use App\Share\Services\Program\ProgramSelectionService;
use App\Share\Services\Subscription\SubscriptionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class SubscriptionsSeeder extends Seeder
{
    public function run(): void
    {
        $subscriptionService = app(SubscriptionService::class);
        $programSelectionService = app(ProgramSelectionService::class);

        $programIds = Program::query()->pluck('id')->all();

        if (count($programIds) < 2) {
            throw new \RuntimeException(
                'SubscriptionsSeeder cần ít nhất 2 program (chạy ProgramsSeeder trước).'
            );
        }

        $expiresAt = now()->addYear();

        User::query()->eachById(function (User $user) use (
            $subscriptionService,
            $programSelectionService,
            $programIds,
            $expiresAt,
        ): void {
            $plan = $this->pickPlan();

            $subscription = $subscriptionService->adminUpsert(
                $user,
                $plan,
                SubscriptionStatus::Active,
                $expiresAt,
                true,
            );

            $selectionIds = $this->pickProgramIds($plan, $programIds);

            $programSelectionService->adminSyncSelections(
                $subscription,
                $user,
                $plan,
                $selectionIds,
            );
        });
    }

    private function pickPlan(): string
    {
        $roll = random_int(1, 100);

        if ($roll <= 70) {
            return Plan::All;
        }

        if ($roll <= 85) {
            return Plan::Basic;
        }

        return Plan::Plus;
    }

    /**
     * @param  list<int>  $programIds
     * @return list<int>
     */
    private function pickProgramIds(string $plan, array $programIds): array
    {
        if ($plan === Plan::All) {
            return [];
        }

        $count = $plan === Plan::Basic ? 1 : 2;

        /** @var Collection<int, int> $shuffled */
        $shuffled = collect($programIds)->shuffle();

        return $shuffled->take($count)->values()->all();
    }
}
