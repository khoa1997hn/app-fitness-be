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
        $lessons = [
            ['type' => LessonType::Level, 'level' => Level::Beginner, 'day' => 1, 'vi' => 'Bài nhập môn', 'en' => 'Beginner lesson'],
            ['type' => LessonType::Level, 'level' => Level::Intermediate, 'day' => 2, 'vi' => 'Bài trung cấp', 'en' => 'Intermediate lesson'],
            ['type' => LessonType::Special, 'level' => null, 'day' => 1, 'vi' => 'Bài đặc biệt', 'en' => 'Special lesson'],
        ];

        foreach ($lessons as $data) {
            $thumbnail = [
                'path' => 'lesson/thumbnail/sample.jpg',
                'name' => 'sample.jpg',
                'extension' => 'jpg',
                'size' => 102400,
            ];

            $lesson = new Lesson([
                'program_id' => $program->id,
                'type' => $data['type'],
                'level' => $data['level'],
                'day' => $data['day'],
            ]);
            $lesson->fill([
                'vi' => ['name' => $data['vi'], 'description' => "Mô tả {$data['vi']}.", 'thumbnail' => $thumbnail],
                'en' => ['name' => $data['en'], 'description' => "{$data['en']} description.", 'thumbnail' => $thumbnail],
            ]);
            $lesson->save();

            $this->seedVideo($lesson->id);
        }
    }

    private function seedVideo(int $lessonId): void
    {
        $video = new Video(['lesson_id' => $lessonId]);
        $video->fill([
            'vi' => [
                'file' => [
                    'path' => 'lesson/video/sample-vi.mp4',
                    'name' => 'sample-vi.mp4',
                    'extension' => 'mp4',
                    'size' => 5242880,
                ],
                'duration_seconds' => 600,
            ],
            'en' => [
                'file' => [
                    'path' => 'lesson/video/sample-en.mp4',
                    'name' => 'sample-en.mp4',
                    'extension' => 'mp4',
                    'size' => 5242880,
                ],
                'duration_seconds' => 600,
            ],
        ]);
        $video->save();
    }
}
