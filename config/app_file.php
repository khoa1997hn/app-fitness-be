<?php

use App\Share\Enums\FileType;

return [
    'default_disk' => env('FILE_UPLOAD_DISK', 's3'),
    'presigned_expires_minutes' => (int) env('AWS_PRESIGNED_URL_EXPIRES', 15),

    FileType::BannerImage => [
        'disk' => 's3',
        'prefix_path' => 'banner/image',
        'allow_mimetypes' => ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
        'allow_max_size' => 5120, // KB (5MB)
    ],
    FileType::ProgramCover => [
        'disk' => 's3',
        'prefix_path' => 'program/cover',
        'allow_mimetypes' => ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
        'allow_max_size' => 5120, // KB (5MB)
    ],
    FileType::LessonVideo => [
        'disk' => 's3',
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
    FileType::LessonThumbnail => [
        'disk' => 's3',
        'prefix_path' => 'lesson/thumbnail',
        'allow_mimetypes' => ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
        'allow_max_size' => 5120, // KB (5MB)
    ],
    FileType::ComboCover => [
        'disk' => 's3',
        'prefix_path' => 'combo/cover',
        'allow_mimetypes' => ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
        'allow_max_size' => 5120, // KB (5MB)
    ],
    FileType::ComboInfoIcon => [
        'disk' => 's3',
        'prefix_path' => 'combo/info-icon',
        'allow_mimetypes' => ['image/png'],
        'allow_max_size' => 5120, // KB (5MB)
    ],
];
