<?php

declare(strict_types=1);

use App\Share\Enums\LessonType;
use App\Share\Enums\Level;

// Label hiển thị cho enum (BenSampo LocalizedEnum).
// Key: <EnumClass>::class => [<enum value> => 'label'].
return [
    LessonType::class => [
        LessonType::Level => 'Theo cấp độ',
        LessonType::Special => 'Đặc biệt',
        LessonType::Signature => 'Đặc trưng',
    ],

    Level::class => [
        Level::Beginner => 'Người mới',
        Level::Intermediate => 'Trung cấp',
        Level::Advanced => 'Nâng cao',
    ],
];
