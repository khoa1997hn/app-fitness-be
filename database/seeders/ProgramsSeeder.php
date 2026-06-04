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

            $cover = [
                'path' => 'program/cover/sample.jpg',
                'name' => 'sample.jpg',
                'extension' => 'jpg',
                'size' => 102400,
            ];

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
        $thumbnail = $this->lessonThumbnail();

        foreach ([Level::Beginner, Level::Intermediate, Level::Advanced] as $level) {
            for ($day = 1; $day <= 10; $day++) {
                [$vi, $en] = $this->levelLessonNames($nameVi, $nameEn, $level, $day);
                $this->createLesson($program, LessonType::Level, $level, $day, $vi, $en, $thumbnail);
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
                $thumbnail,
            );
            $this->createLesson(
                $program,
                LessonType::Signature,
                null,
                $day,
                "{$nameVi} — Signature {$day}",
                "{$nameEn} — Signature {$day}",
                $thumbnail,
            );
        }
    }

    /**
     * @return array{name: string, path: string, size: int, extension: string}
     */
    private function lessonThumbnail(): array
    {
        return [
            'name' => '7lkbLprBMslkQglRV2PCcZvb1bqQuwRnscKAK5LK.jpg',
            'path' => 'lesson/thumbnail/7lkbLprBMslkQglRV2PCcZvb1bqQuwRnscKAK5LK.jpg',
            'size' => 377855,
            'extension' => 'jpg',
        ];
    }

    /**
     * @return array{name: string, path: string, size: int, extension: string}
     */
    private function lessonVideoFile(): array
    {
        return [
            'name' => 'g2x0MZqzF8uAMrfj0hLKoVMxwZoLuVw7poZJGjhJ.mp4',
            'path' => 'lesson/video/g2x0MZqzF8uAMrfj0hLKoVMxwZoLuVw7poZJGjhJ.mp4',
            'size' => 266176052,
            'extension' => 'mp4',
        ];
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

    /**
     * @param  array{name: string, path: string, size: int, extension: string}  $thumbnail
     */
    private function createLesson(
        Program $program,
        string $type,
        ?string $level,
        int $day,
        string $nameVi,
        string $nameEn,
        array $thumbnail,
    ): void {
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
        $file = $this->lessonVideoFile();
        $videoPayload = [
            'file' => $file,
            'duration_seconds' => 600,
        ];

        $video = new Video(['lesson_id' => $lessonId]);
        $video->fill([
            'vi' => $videoPayload,
            'en' => $videoPayload,
        ]);
        $video->save();
    }
}
