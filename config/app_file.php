<?php

use App\Share\Enums\FileType;

return [
    FileType::BannerImage => [
        'prefix_path' => 'banner/image',
        'allow_mimetypes' => ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
        'allow_max_size' => 5120, // KB (5MB)
    ],
    FileType::ProgramCover => [
        'prefix_path' => 'program/cover',
        'allow_mimetypes' => ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
        'allow_max_size' => 5120, // KB (5MB)
    ],
    FileType::LessonVideo => [
        'prefix_path' => 'lesson/video',
        'allow_mimetypes' => [
            'video/mp4',
            'video/quicktime',
            'video/x-m4v',
            'video/3gpp',
            'video/3gpp2',
            'video/webm',
        ],
        'allow_max_size' => 1048576, // KB (1GB)
    ],
];
