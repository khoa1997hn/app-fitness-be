<?php

declare(strict_types=1);

namespace App\Share\Services\Video;

use App\Share\Models\Lesson;
use App\Share\Models\Program;
use App\Share\Models\User;
use App\Share\Models\UserVideoProgress;
use App\Share\Models\Video;
use Illuminate\Database\Eloquent\Collection;

class VideoWatchProgressService
{
    /**
     * Record progress and return video + lesson + program progress objects.
     *
     * @return array{video: array<string, mixed>, lesson: array<string, mixed>, program: array<string, mixed>}
     */
    public function record(User $user, Video $video, int $watchedSeconds, bool $isCompleted): array
    {
        $progress = UserVideoProgress::query()->firstOrNew([
            'user_id' => $user->id,
            'video_id' => $video->id,
        ]);

        $progress->watched_seconds = max((int) ($progress->watched_seconds ?? 0), $watchedSeconds);
        $progress->is_completed = $progress->is_completed || $isCompleted;
        $progress->last_watched_at = now();
        $progress->save();

        $video->loadMissing(['lesson.program']);
        $lesson = $video->lesson;
        $program = $lesson->program;

        return [
            'video' => [
                'id' => $video->id,
                'lesson_id' => $video->lesson_id,
                'duration_seconds' => (int) $video->duration_seconds,
                'progress' => [
                    'watched_seconds' => $progress->watched_seconds,
                    'completed_percent' => $progress->is_completed ? 100 : 0,
                ],
            ],
            'lesson' => [
                'id' => $lesson->id,
                'progress' => $this->lessonProgress($user, $lesson),
            ],
            'program' => [
                'id' => $program->id,
                'progress' => $this->programProgress($user, $program),
            ],
        ];
    }

    /**
     * Progress object for a single video.
     *
     * @return array{watched_seconds: int, completed_percent: int}
     */
    public function videoProgress(User $user, Video $video): array
    {
        $row = UserVideoProgress::query()
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->first(['watched_seconds', 'is_completed']);

        return [
            'watched_seconds' => (int) ($row?->watched_seconds ?? 0),
            'completed_percent' => $row?->is_completed ? 100 : 0,
        ];
    }

    /**
     * Progress object for a single lesson.
     *
     * @return array{watched_seconds: int, completed_percent: int}
     */
    public function lessonProgress(User $user, Lesson $lesson): array
    {
        return $this->lessonProgressMapForUser($user, [$lesson->id])[$lesson->id];
    }

    /**
     * Progress object for a single program.
     *
     * @return array{watched_seconds: int, completed_percent: int}
     */
    public function programProgress(User $user, Program $program): array
    {
        return $this->programProgressMapForUser($user, [$program->id])[$program->id];
    }

    /**
     * Batch progress for multiple lessons.
     *
     * @param  array<int>  $lessonIds
     * @return array<int, array{watched_seconds: int, completed_percent: int}>
     */
    public function lessonProgressMapForUser(User $user, array $lessonIds): array
    {
        if ($lessonIds === []) {
            return [];
        }

        $videos = $this->videosWithUserProgress($user)
            ->whereIn('lesson_id', $lessonIds)
            ->get(['id', 'lesson_id']);

        return array_replace(
            array_fill_keys($lessonIds, $this->emptyProgress()),
            $this->progressMap($videos, fn (Video $video): int => $video->lesson_id),
        );
    }

    /**
     * Batch progress for multiple programs.
     *
     * @param  array<int>  $programIds
     * @return array<int, array{watched_seconds: int, completed_percent: int}>
     */
    public function programProgressMapForUser(User $user, array $programIds): array
    {
        if ($programIds === []) {
            return [];
        }

        $videos = $this->videosWithUserProgress($user)
            ->whereHas('lesson', fn ($query) => $query->whereIn('program_id', $programIds))
            ->with('lesson:id,program_id')
            ->get(['id', 'lesson_id']);

        return array_replace(
            array_fill_keys($programIds, $this->emptyProgress()),
            $this->progressMap($videos, fn (Video $video): int => $video->lesson->program_id),
        );
    }

    /**
     * Return all programs the user has watch progress for, sorted by most recently watched.
     *
     * @return array<int, array<string, mixed>>
     */
    public function leftOffProgram(User $user): array
    {
        // All user progress, newest first — the first row seen per program is its latest watch.
        $progresses = UserVideoProgress::query()
            ->where('user_id', $user->id)
            ->whereNotNull('last_watched_at')
            ->with(['video:id,lesson_id', 'video.lesson:id,program_id'])
            ->orderByDesc('last_watched_at')
            ->get(['id', 'video_id', 'last_watched_at']);

        $latestPerProgram = [];
        foreach ($progresses as $progress) {
            $lesson = $progress->video?->lesson;
            if ($lesson === null || isset($latestPerProgram[$lesson->program_id])) {
                continue;
            }

            $latestPerProgram[$lesson->program_id] = [
                'video_id' => $progress->video_id,
                'lesson_id' => $progress->video->lesson_id,
                'program_id' => $lesson->program_id,
            ];
        }

        if ($latestPerProgram === []) {
            return [];
        }

        $programIds = array_keys($latestPerProgram);
        $lessonIds = array_column($latestPerProgram, 'lesson_id');

        $progressMap = $this->programProgressMapForUser($user, $programIds);
        $durationMap = $this->programDurationMap($programIds);
        $programs = Program::withTranslation()->whereIn('id', $programIds)->get()->keyBy('id');
        $lessons = Lesson::withTranslation()->whereIn('id', $lessonIds)->get()->keyBy('id');

        return array_map(function (array $row) use ($programs, $lessons, $progressMap, $durationMap): array {
            $program = $programs[$row['program_id']];
            $lesson = $lessons[$row['lesson_id']];

            return [
                'id' => $program->id,
                'name' => $program->name,
                'cover' => $program->cover,
                'duration_seconds' => $durationMap[$row['program_id']] ?? 0,
                'progress' => $progressMap[$row['program_id']] ?? $this->emptyProgress(),
                'last_lesson' => [
                    'id' => $lesson->id,
                    'name' => $lesson->name,
                    'day' => $lesson->day,
                ],
            ];
        }, array_values($latestPerProgram));
    }

    /**
     * Base query: videos carrying the user's watched/completed aggregates.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Video>
     */
    private function videosWithUserProgress(User $user)
    {
        return Video::query()
            ->withSum(
                ['progresses as watched_seconds' => fn ($query) => $query->where('user_id', $user->id)],
                'watched_seconds',
            )
            ->withCount([
                'progresses as completed_count' => fn ($query) => $query
                    ->where('user_id', $user->id)
                    ->where('is_completed', true),
            ]);
    }

    /**
     * Aggregate watched seconds + completed percent for videos grouped by a key.
     *
     * @param  Collection<int, Video>  $videos
     * @param  callable(Video): int  $groupBy
     * @return array<int, array{watched_seconds: int, completed_percent: int}>
     */
    private function progressMap(Collection $videos, callable $groupBy): array
    {
        $map = [];
        foreach ($videos->groupBy($groupBy) as $key => $group) {
            $total = $group->count();
            $completed = (int) $group->sum('completed_count');

            $map[(int) $key] = [
                'watched_seconds' => (int) $group->sum('watched_seconds'),
                'completed_percent' => $total > 0 ? (int) round($completed * 100 / $total) : 0,
            ];
        }

        return $map;
    }

    /**
     * Total duration (current locale) per program, in seconds.
     *
     * @param  array<int>  $programIds
     * @return array<int, int>
     */
    private function programDurationMap(array $programIds): array
    {
        $videos = Video::query()
            ->whereHas('lesson', fn ($query) => $query->whereIn('program_id', $programIds))
            ->with([
                'lesson:id,program_id',
                'translations' => fn ($query) => $query->where('locale', config('app.locale')),
            ])
            ->get(['id', 'lesson_id']);

        $map = array_fill_keys($programIds, 0);
        foreach ($videos->groupBy(fn (Video $video): int => $video->lesson->program_id) as $programId => $group) {
            $map[(int) $programId] = (int) $group->sum(
                fn (Video $video): int => (int) ($video->translations->first()->duration_seconds ?? 0),
            );
        }

        return $map;
    }

    /**
     * @return array{watched_seconds: int, completed_percent: int}
     */
    private function emptyProgress(): array
    {
        return ['watched_seconds' => 0, 'completed_percent' => 0];
    }
}
