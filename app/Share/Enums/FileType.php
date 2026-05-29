<?php

declare(strict_types=1);

namespace App\Share\Enums;

final class FileType extends Enum
{
    const BannerImage = 'banner_image';

    const ProgramCover = 'program_cover';

    const LessonVideo = 'lesson_video';

    const LessonThumbnail = 'lesson_thumbnail';
}
