<?php

declare(strict_types=1);

use App\Share\Enums\LessonType;
use App\Share\Enums\Level;

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
];
