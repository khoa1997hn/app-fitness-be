<?php

namespace App\Web\Http\Controllers\API\V1\Concerns;

use App\Share\Enums\LessonType;
use App\Share\Enums\Level;
use App\Share\Models\Lesson;
use App\Share\Models\Program;
use App\Share\Models\User;
use App\Share\Services\Video\VideoWatchProgressService;
use Illuminate\Support\Collection;

trait MapsProgramForApi
{
    use MapsLessonForApi;

    /**
     * @return array<string, mixed>
     */
    protected function programRelations(): array
    {
        return [
            'lessons' => fn ($query) => $query->withTranslation()
                ->with(['videos' => fn ($videoQuery) => $videoQuery->withTranslation()]),
            'goals' => fn ($query) => $query->withTranslation()->orderBy('sort'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapProgram(Program $program): array
    {
        $totalSeconds = $program->lessons
            ->sum(fn (Lesson $lesson) => $lesson->videos->sum('duration_seconds'));

        return [
            'id' => $program->id,
            'name' => $program->name,
            'description' => $program->description,
            'cover' => $program->cover,
            'rating' => $program->rating,
            'duration_minutes' => (int) round($totalSeconds / 60),
            'course_count' => $program->lessons->count(),
            'goals' => $program->goals->map(fn ($goal) => $goal->content)->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapProgramDetail(
        Program $program,
        User $user,
        VideoWatchProgressService $videoWatchProgressService,
    ): array {
        $program->loadMissing($this->programRelations());

        $favoritedIds = $user->favoriteLessons()
            ->whereIn('lessons.id', $program->lessons->pluck('id'))
            ->pluck('lessons.id')
            ->all();

        $lessonIds = $program->lessons->pluck('id')->all();
        $lessonProgressMap = $videoWatchProgressService->lessonProgressMapForUser($user, $lessonIds);
        $programProgressData = $videoWatchProgressService->programProgress($user, $program);

        return [
            ...$this->mapProgram($program),
            'progress' => $programProgressData,
            'lessons' => $this->groupLessons($program->lessons, $favoritedIds, $lessonProgressMap),
        ];
    }

    /**
     * @param  list<int>  $favoritedIds
     * @param  array<int, array{watched_seconds: int, completed_percent: int}>  $lessonProgressMap
     * @return array<string, mixed>
     */
    protected function groupLessons(Collection $lessons, array $favoritedIds, array $lessonProgressMap): array
    {
        $sorted = $this->sortLessons($lessons);

        $levelLessons = $sorted->filter(fn (Lesson $lesson) => $lesson->type->is(LessonType::Level));

        return [
            'level' => [
                'beginner' => $this->mapLessonsCollection(
                    $levelLessons->filter(fn (Lesson $lesson) => $lesson->level?->is(Level::Beginner)),
                    $favoritedIds,
                    $lessonProgressMap
                ),
                'intermediate' => $this->mapLessonsCollection(
                    $levelLessons->filter(fn (Lesson $lesson) => $lesson->level?->is(Level::Intermediate)),
                    $favoritedIds,
                    $lessonProgressMap
                ),
                'advanced' => $this->mapLessonsCollection(
                    $levelLessons->filter(fn (Lesson $lesson) => $lesson->level?->is(Level::Advanced)),
                    $favoritedIds,
                    $lessonProgressMap
                ),
            ],
            'special' => $this->mapLessonsCollection(
                $sorted->filter(fn (Lesson $lesson) => $lesson->type->is(LessonType::Special)),
                $favoritedIds,
                $lessonProgressMap
            ),
            'signature' => $this->mapLessonsCollection(
                $sorted->filter(fn (Lesson $lesson) => $lesson->type->is(LessonType::Signature)),
                $favoritedIds,
                $lessonProgressMap
            ),
        ];
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @param  list<int>  $favoritedIds
     * @param  array<int, array{watched_seconds: int, completed_percent: int}>  $lessonProgressMap
     * @return list<array<string, mixed>>
     */
    protected function mapLessonsCollection(Collection $lessons, array $favoritedIds, array $lessonProgressMap): array
    {
        return $lessons
            ->map(fn (Lesson $lesson) => $this->mapLessonForApi(
                $lesson,
                in_array($lesson->id, $favoritedIds, true),
                $lessonProgressMap
            ))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Lesson>  $lessons
     * @return Collection<int, Lesson>
     */
    protected function sortLessons(Collection $lessons): Collection
    {
        return $lessons->sortBy([
            fn (Lesson $lesson) => $lesson->day,
            fn (Lesson $lesson) => $lesson->id,
        ])->values();
    }
}
