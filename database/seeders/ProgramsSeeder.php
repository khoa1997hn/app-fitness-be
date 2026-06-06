<?php

namespace Database\Seeders;

use App\Share\Enums\LessonType;
use App\Share\Enums\Level;
use App\Share\Models\Lesson;
use App\Share\Models\Program;
use App\Share\Models\ProgramGoal;
use App\Share\Models\Video;
use Illuminate\Database\Seeder;

class ProgramsSeeder extends Seeder
{
    /**
     * Object key S3 đã upload sẵn — seeder random từ pool.
     *
     * @var list<string>
     */
    private const PROGRAM_COVER_PATHS = [
        'program/cover/LYB0r26N4mea4fhy6ABJfRPsBbp0TA1kYjh8tolp.jpg',
        'program/cover/K6PbHcUZnjzdJ0xmQDvym5YiFpq4Se0xMnmAjkeD.jpg',
    ];

    /**
     * @var list<string>
     */
    private const LESSON_THUMBNAIL_PATHS = [
        'lesson/thumbnail/JBZubf0yvag8uR74fZTovDt1wk3lCVC4zUirQDW1.jpg',
        'lesson/thumbnail/MMhxN3JMiDsYcsMZ8oGF0pEN0QQzhBFUQ3afIOtk.jpg',
    ];

    /**
     * @var list<string>
     */
    private const LESSON_VIDEO_PATHS = [
        'lesson/video/5LVjU1yCw4OvN976I5DIMjQ6dFzSm46QaeLUT3qU.mp4',
    ];

    private const LESSON_VIDEO_DEFAULT_SIZE = 266_176_052;

    private const LESSON_VIDEO_DURATION_SECONDS = 600;

    /**
     * Tên 7 program (vi => en).
     *
     * @var array<string, string>
     */
    private array $programs = [
        'Yoga' => 'Yoga',
        'Mat Pilates' => 'Mat Pilates',
        'Reformer Pilates' => 'Reformer Pilates',
        'Sculpt' => 'Sculpt',
        'Breathwork' => 'Breathwork',
        'Wellness' => 'Wellness',
        'Barre' => 'Barre',
    ];

    public function run(): void
    {
        $sort = 0;

        foreach ($this->programs as $nameVi => $nameEn) {
            $program = Program::create(['rating' => 4.5]);

            $cover = $this->randomFile(self::PROGRAM_COVER_PATHS);

            $program->fill([
                'vi' => [
                    'name' => $nameVi,
                    'description' => "Mô tả chương trình {$nameVi}.",
                    'cover' => $cover,
                    'sort' => $sort,
                ],
                'en' => [
                    'name' => $nameEn,
                    'description' => "{$nameEn} program description.",
                    'cover' => $cover,
                    'sort' => $sort,
                ],
            ]);
            $program->save();

            $this->seedGoals($program);
            $this->seedLessons($program);

            $sort++;
        }
    }

    private function seedGoals(Program $program): void
    {
        $goals = [
            ['vi' => 'Cải thiện sức khỏe', 'en' => 'Improve health'],
            ['vi' => 'Tăng sự dẻo dai', 'en' => 'Increase flexibility'],
            ['vi' => 'Giảm căng thẳng', 'en' => 'Reduce stress'],
        ];

        foreach ($goals as $index => $goal) {
            $programGoal = new ProgramGoal(['program_id' => $program->id, 'sort' => $index]);
            $programGoal->fill([
                'vi' => ['content' => $goal['vi']],
                'en' => ['content' => $goal['en']],
            ]);
            $programGoal->save();
        }
    }

    private function seedLessons(Program $program): void
    {
        $nameVi = $program->translate('vi')->name;
        $nameEn = $program->translate('en')->name;

        foreach ([Level::Beginner, Level::Intermediate, Level::Advanced] as $level) {
            for ($day = 1; $day <= 10; $day++) {
                [$vi, $en] = $this->levelLessonNames($nameVi, $nameEn, $level, $day);
                $this->createLesson($program, LessonType::Level, $level, $day, $vi, $en);
            }
        }

        for ($day = 1; $day <= 10; $day++) {
            $this->createLesson(
                $program,
                LessonType::Special,
                null,
                $day,
                "{$nameVi} — Đặc biệt {$day}",
                "{$nameEn} — Special {$day}",
            );
            $this->createLesson(
                $program,
                LessonType::Signature,
                null,
                $day,
                "{$nameVi} — Signature {$day}",
                "{$nameEn} — Signature {$day}",
            );
        }
    }

    /**
     * @param  list<string>  $paths
     * @return array{name: string, path: string, size: int, extension: string}
     */
    private function randomFile(array $paths, ?int $size = null): array
    {
        return $this->fileFromPath($this->pickRandomPath($paths), $size);
    }

    /**
     * @param  list<string>  $paths
     */
    private function pickRandomPath(array $paths): string
    {
        return $paths[array_rand($paths)];
    }

    /**
     * @return array{name: string, path: string, size: int, extension: string}
     */
    private function fileFromPath(string $path, ?int $size = null): array
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION) ?: '';

        return [
            'path' => $path,
            'name' => basename($path),
            'extension' => $extension,
            'size' => $size ?? $this->defaultSizeForExtension($extension),
        ];
    }

    private function defaultSizeForExtension(string $extension): int
    {
        return match ($extension) {
            'mp4', 'mov', 'webm' => self::LESSON_VIDEO_DEFAULT_SIZE,
            default => 377_855,
        };
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function levelLessonNames(string $nameVi, string $nameEn, string $level, int $day): array
    {
        $labels = [
            Level::Beginner => ['vi' => 'Cơ bản', 'en' => 'Beginner'],
            Level::Intermediate => ['vi' => 'Trung cấp', 'en' => 'Intermediate'],
            Level::Advanced => ['vi' => 'Nâng cao', 'en' => 'Advanced'],
        ];

        $label = $labels[$level];

        return [
            "{$nameVi} — {$label['vi']} — Ngày {$day}",
            "{$nameEn} — {$label['en']} — Day {$day}",
        ];
    }

    private function createLesson(
        Program $program,
        string $type,
        ?string $level,
        int $day,
        string $nameVi,
        string $nameEn,
    ): void {
        $thumbnail = $this->randomFile(self::LESSON_THUMBNAIL_PATHS);

        $lesson = new Lesson([
            'program_id' => $program->id,
            'type' => $type,
            'level' => $level,
            'day' => $day,
        ]);
        $lesson->fill([
            'vi' => [
                'name' => $nameVi,
                'description' => "Mô tả {$nameVi}.",
                'teacher_name' => 'Nguyễn Văn A',
                'thumbnail' => $thumbnail,
            ],
            'en' => [
                'name' => $nameEn,
                'description' => "{$nameEn} description.",
                'teacher_name' => 'John Smith',
                'thumbnail' => $thumbnail,
            ],
        ]);
        $lesson->save();

        $this->seedVideo($lesson->id);
    }

    private function seedVideo(int $lessonId): void
    {
        $file = $this->randomFile(self::LESSON_VIDEO_PATHS, self::LESSON_VIDEO_DEFAULT_SIZE);
        $videoPayload = [
            'file' => $file,
            'duration_seconds' => self::LESSON_VIDEO_DURATION_SECONDS,
        ];

        $video = new Video(['lesson_id' => $lessonId]);
        $video->fill([
            'vi' => $videoPayload,
            'en' => $videoPayload,
        ]);
        $video->save();
    }
}
