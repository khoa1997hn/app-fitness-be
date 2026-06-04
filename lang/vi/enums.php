<?php

declare(strict_types=1);

use App\Share\Enums\LessonType;
use App\Share\Enums\Level;
use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionProvider;
use App\Share\Enums\SubscriptionStatus;

// Label hiển thị cho enum (BenSampo LocalizedEnum).
// Key: <EnumClass>::class => [<enum value> => 'label'].
return [
    LessonType::class => [
        LessonType::Level => 'Level',
        LessonType::Special => 'Special',
        LessonType::Signature => 'Signature',
    ],

    Level::class => [
        Level::Beginner => 'Beginner',
        Level::Intermediate => 'Intermediate',
        Level::Advanced => 'Advanced',
    ],

    Plan::class => [
        Plan::Basic => 'Basic',
        Plan::Plus => 'Plus',
        Plan::All => 'All Access',
    ],

    SubscriptionStatus::class => [
        SubscriptionStatus::Trial => 'Dùng thử',
        SubscriptionStatus::Active => 'Còn hiệu lực',
        SubscriptionStatus::Expired => 'Hết hạn',
        SubscriptionStatus::Cancelled => 'Đã hủy',
        SubscriptionStatus::GracePeriod => 'Gia hạn',
        SubscriptionStatus::Refunded => 'Hoàn tiền',
    ],

    SubscriptionProvider::class => [
        SubscriptionProvider::GoogleIap => 'Google IAP',
        SubscriptionProvider::AppleIap => 'Apple IAP',
        SubscriptionProvider::Admin => 'Admin (thủ công)',
    ],
];
