<?php

declare(strict_types=1);

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\BookingStatus;
use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\FitnessClassCategory;
use App\Share\Enums\LessonType;
use App\Share\Enums\Level;
use App\Share\Enums\OrderStatus;
use App\Share\Enums\OrderType;
use App\Share\Enums\PackageStatus;
use App\Share\Enums\PackageType;
use App\Share\Enums\PaymentMethod;
use App\Share\Enums\Plan;
use App\Share\Enums\PrivateDiscipline;
use App\Share\Enums\Role;
use App\Share\Enums\SubscriptionProvider;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Enums\TherapyVariant;

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

    Role::class => [
        Role::Admin => 'Quản trị hệ thống',
        Role::Receptionist => 'Lễ tân',
        Role::Trainer => 'Huấn luyện viên',
        Role::Member => 'Hội viên',
    ],

    PackageType::class => [
        PackageType::DropIn => 'Drop-in',
        PackageType::ClassPack => 'Gói theo buổi',
        PackageType::Unlimited => 'Không giới hạn',
        PackageType::IntroDemo => 'Học thử',
        PackageType::PrivatePack => 'Private',
        PackageType::PrivateGroup => 'Private nhóm',
    ],

    FitnessClassCategory::class => [
        FitnessClassCategory::Sculpt => 'SCULPT',
        FitnessClassCategory::CorePilates => 'CORE & PILATES',
        FitnessClassCategory::YogaSound => 'YOGA & SOUND HEALING',
        FitnessClassCategory::Barre => 'BARRE',
        FitnessClassCategory::MeditationSound => 'MEDITATION & SOUND HEALING',
        FitnessClassCategory::DanceCardio => 'DANCE & CARDIO',
    ],

    PrivateDiscipline::class => [
        PrivateDiscipline::Yoga => 'Yoga',
        PrivateDiscipline::Pilates => 'Pilates',
        PrivateDiscipline::Barre => 'Barre',
    ],

    BookingRequestStatus::class => [
        BookingRequestStatus::Pending => 'Chưa duyệt',
        BookingRequestStatus::Confirmed => 'Đã duyệt',
        BookingRequestStatus::Rejected => 'Đã từ chối',
        BookingRequestStatus::Cancelled => 'Đã hủy',
        BookingRequestStatus::Completed => 'Đã tập',
    ],

    ClassScheduleStatus::class => [
        ClassScheduleStatus::Active => 'Đang mở',
        ClassScheduleStatus::Cancelled => 'Đã hủy',
    ],

    BookingStatus::class => [
        BookingStatus::Confirmed => 'Đã đặt',
        BookingStatus::Cancelled => 'Đã hủy',
    ],

    OrderType::class => [
        OrderType::Package => 'Gói tập',
        OrderType::Therapy => 'Trị liệu',
        OrderType::ExtraPeople => 'Suất người thêm',
    ],

    OrderStatus::class => [
        OrderStatus::PendingTerms => 'Chờ ký điều khoản',
        OrderStatus::Pending => 'Chờ thanh toán',
        OrderStatus::Success => 'Đã thanh toán',
    ],

    PaymentMethod::class => [
        PaymentMethod::Momo => 'MoMo',
        PaymentMethod::Vnpay => 'VNPay',
        PaymentMethod::Cash => 'Tiền mặt',
    ],

    TherapyVariant::class => [
        TherapyVariant::Single => '1 buổi lẻ',
        TherapyVariant::Combo5 => 'Combo 5 buổi',
    ],

    PackageStatus::class => [
        PackageStatus::Active => 'Đang bán',
        PackageStatus::Hidden => 'Đã ẩn',
    ],
];
