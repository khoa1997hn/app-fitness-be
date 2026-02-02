<?php

use App\Share\Enums\FileType;

return [
    FileType::BannerImage => [
        'prefix_path' => 'banner/image',
        'allow_mimetypes' => ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'],
        'allow_max_size' => 5120, // KB (5MB)
    ],
];
