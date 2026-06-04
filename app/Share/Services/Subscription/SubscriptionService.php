<?php

namespace App\Share\Services\Subscription;

use App\Share\Enums\BillingCycle;
use App\Share\Enums\LessonType;
use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionProvider;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Exceptions\Subscription\SubscriptionNotFoundException;
use App\Share\Models\Subscription;
use App\Share\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Psr\Log\LoggerInterface;

class SubscriptionService
{
    protected LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = Log::channel('subscription');
    }

    /**
     * Admin tạo hoặc cập nhật subscription (chỉ plan, status, expires_at, auto_renew).
     */
    public function adminUpsert(
        User $user,
        string $plan,
        string $status,
        ?Carbon $expiresAt,
        bool $autoRenew
    ): Subscription {
        return DB::transaction(function () use ($user, $plan, $status, $expiresAt, $autoRenew) {
            $existing = Subscription::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $coreData = [
                'plan' => $plan,
                'status' => $status,
                'expires_at' => $expiresAt,
                'auto_renew' => $autoRenew,
            ];

            if ($existing) {
                $existing->update($coreData);
                $subscription = $existing->fresh();
            } else {
                $subscription = Subscription::query()->create([
                    'user_id' => $user->id,
                    ...$coreData,
                    'provider' => SubscriptionProvider::Admin,
                    'provider_subscription_id' => null,
                    'amount' => (float) config('app_payment.plans.'.$plan.'.price'),
                    'currency' => config('app_payment.currency'),
                    'billing_cycle' => BillingCycle::Monthly,
                ]);
            }

            $user->update([
                'plan' => $plan,
                'subscription_status' => $status,
            ]);

            $this->logger->info('[Admin] Subscription upsert', [
                'user_id' => $user->id,
                'admin_id' => auth('admin')->id(),
                'subscription_id' => $subscription->id,
                'created' => $existing === null,
                'plan' => $plan,
                'status' => $status,
            ]);

            return $subscription;
        });
    }

    /**
     * Activate subscription for user
     */
    public function activate(
        User $user,
        Plan $plan,
        string $provider,
        string $providerSubscriptionId,
        float $amount,
        ?Carbon $expiresAt = null,
        bool $autoRenew = true,
        ?array $metadata = null
    ): Subscription {
        return DB::transaction(function () use (
            $user,
            $plan,
            $provider,
            $providerSubscriptionId,
            $expiresAt,
            $autoRenew,
            $amount,
            $metadata
        ) {
            // Lock subscription để đảm bảo thread-safe
            Subscription::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            $subscription = Subscription::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'plan' => $plan,
                    'provider' => $provider,
                    'status' => SubscriptionStatus::Active,
                    'provider_subscription_id' => $providerSubscriptionId,
                    'expires_at' => $expiresAt,
                    'auto_renew' => $autoRenew,
                    'amount' => $amount,
                    'currency' => config('app_payment.currency'),
                    'billing_cycle' => BillingCycle::Monthly,
                    'metadata' => $metadata,
                ]
            );

            // Update user subscription status and plan
            $user->update([
                'subscription_status' => SubscriptionStatus::Active,
                'plan' => $plan,
            ]);

            return $subscription;
        });
    }

    /**
     * Renew subscription
     */
    public function renew(Subscription $subscription, ?Carbon $expiresAt = null): Subscription
    {
        return $this->updateSubscription(
            $subscription,
            [
                'status' => SubscriptionStatus::Active,
                'expires_at' => $expiresAt,
                'auto_renew' => true,
            ],
            SubscriptionStatus::Active
        );
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

    /**
     * Subscription data for GET /subscriptions/me response (without selected_programs).
     * Controller is responsible for appending selected_programs via ProgramSelectionService.
     *
     * @return ?array<string, mixed>
     */
    public function getSubscriptionData(User $user): ?array
    {
        $subscription = $user->subscription;

        if ($subscription === null) {
            return null;
        }

        $limits = $this->getPlanLimits($subscription->plan);

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

    /**
     * Cancel subscription
     */
    public function cancel(Subscription $subscription): Subscription
    {
        return $this->updateSubscription(
            $subscription,
            [
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
                'auto_renew' => false,
            ],
            SubscriptionStatus::Cancelled
        );
    }

    /**
     * Expire subscription
     */
    public function expire(Subscription $subscription): Subscription
    {
        return $this->updateSubscription(
            $subscription,
            [
                'status' => SubscriptionStatus::Expired,
            ],
            SubscriptionStatus::Expired
        );
    }

    /**
     * Refund subscription
     */
    public function refund(Subscription $subscription): Subscription
    {
        return $this->updateSubscription(
            $subscription,
            [
                'status' => SubscriptionStatus::Refunded,
                'cancelled_at' => now(),
                'auto_renew' => false,
            ],
            SubscriptionStatus::Refunded
        );
    }

    /**
     * Update subscription with lock and update user
     */
    protected function updateSubscription(
        Subscription $subscription,
        array $subscriptionData,
        string $userStatus
    ): Subscription {
        return DB::transaction(function () use ($subscription, $subscriptionData, $userStatus) {
            // Lock subscription để đảm bảo thread-safe
            $subscription = Subscription::query()
                ->where('id', $subscription->id)
                ->lockForUpdate()
                ->first();

            if (! $subscription) {
                throw new SubscriptionNotFoundException;
            }

            // Update subscription theo dữ liệu từ event
            $subscription->update($subscriptionData);

            // Update user subscription status and plan.
            // Use withTrashed() to handle soft-deleted users (e.g., account deletion flow).
            $subscription->user()->withTrashed()->first()?->update([
                'subscription_status' => $userStatus,
                'plan' => $subscription->plan,
            ]);

            return $subscription->fresh();
        });
    }
}
