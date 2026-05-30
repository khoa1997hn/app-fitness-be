<?php

declare(strict_types=1);

namespace App\Share\Services\Video;

use App\Share\Models\Lesson;
use App\Share\Models\Program;
use App\Share\Models\User;
use App\Share\Models\UserVideoProgress;
use App\Share\Models\Video;
use Illuminate\Support\Facades\DB;

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
     * Progress object for a single lesson — calculated via SQL (no collection loading).
     *
     * @return array{watched_seconds: int, completed_percent: int}
     */
    public function lessonProgress(User $user, Lesson $lesson): array
    {
        $row = DB::table('videos as v')
            ->leftJoin('user_video_progress as uvp', function ($join) use ($user) {
                $join->on('uvp.video_id', '=', 'v.id')->where('uvp.user_id', '=', $user->id);
            })
            ->where('v.lesson_id', $lesson->id)
            ->selectRaw('
                COALESCE(SUM(COALESCE(uvp.watched_seconds, 0)), 0) AS watched_seconds,
                COALESCE(ROUND(COUNT(CASE WHEN uvp.is_completed THEN 1 END) * 100.0 / NULLIF(COUNT(v.id), 0)), 0) AS completed_percent
            ')
            ->first();

        return [
            'watched_seconds' => (int) ($row?->watched_seconds ?? 0),
            'completed_percent' => (int) ($row?->completed_percent ?? 0),
        ];
    }

    /**
     * Progress object for a single program — calculated via SQL (no collection loading).
     *
     * @return array{watched_seconds: int, completed_percent: int}
     */
    public function programProgress(User $user, Program $program): array
    {
        $row = DB::table('videos as v')
            ->join('lessons as l', 'l.id', '=', 'v.lesson_id')
            ->leftJoin('user_video_progress as uvp', function ($join) use ($user) {
                $join->on('uvp.video_id', '=', 'v.id')->where('uvp.user_id', '=', $user->id);
            })
            ->where('l.program_id', $program->id)
            ->selectRaw('
                COALESCE(SUM(COALESCE(uvp.watched_seconds, 0)), 0) AS watched_seconds,
                COALESCE(ROUND(COUNT(CASE WHEN uvp.is_completed THEN 1 END) * 100.0 / NULLIF(COUNT(v.id), 0)), 0) AS completed_percent
            ')
            ->first();

        return [
            'watched_seconds' => (int) ($row?->watched_seconds ?? 0),
            'completed_percent' => (int) ($row?->completed_percent ?? 0),
        ];
    }

    /**
     * Batch progress for multiple programs — one SQL query.
     *
     * @param  array<int>  $programIds
     * @return array<int, array{watched_seconds: int, completed_percent: int}>
     */
    public function programProgressMapForUser(User $user, array $programIds): array
    {
        if ($programIds === []) {
            return [];
        }

        $rows = DB::table('videos as v')
            ->join('lessons as l', 'l.id', '=', 'v.lesson_id')
            ->leftJoin('user_video_progress as uvp', function ($join) use ($user) {
                $join->on('uvp.video_id', '=', 'v.id')->where('uvp.user_id', '=', $user->id);
            })
            ->whereIn('l.program_id', $programIds)
            ->groupBy('l.program_id')
            ->selectRaw('
                l.program_id,
                COALESCE(SUM(COALESCE(uvp.watched_seconds, 0)), 0) AS watched_seconds,
                COALESCE(ROUND(COUNT(CASE WHEN uvp.is_completed THEN 1 END) * 100.0 / NULLIF(COUNT(v.id), 0)), 0) AS completed_percent
            ')
            ->get();

        $default = ['watched_seconds' => 0, 'completed_percent' => 0];
        $map = array_fill_keys($programIds, $default);
        foreach ($rows as $row) {
            $map[$row->program_id] = [
                'watched_seconds' => (int) $row->watched_seconds,
                'completed_percent' => (int) $row->completed_percent,
            ];
        }

        return $map;
    }

    /**
     * Return all programs the user has watch progress for, sorted by most recently watched.
     * Uses a window function to pick the latest video per program in one query,
     * then batch-loads stats and models.
     *
     * @return array<int, array<string, mixed>>
     */
    public function leftOffProgram(User $user): array
    {
        // One query: for each program, get the video with MAX(last_watched_at) using ROW_NUMBER()
        $latestRows = DB::select(
            '
            SELECT video_id, lesson_id, program_id, program_last_watched_at
            FROM (
                SELECT
                    uvp.video_id,
                    v.lesson_id,
                    l.program_id,
                    MAX(uvp.last_watched_at) OVER (PARTITION BY l.program_id) AS program_last_watched_at,
                    ROW_NUMBER() OVER (PARTITION BY l.program_id ORDER BY uvp.last_watched_at DESC) AS rn
                FROM user_video_progress uvp
                JOIN videos v ON v.id = uvp.video_id
                JOIN lessons l ON l.id = v.lesson_id
                WHERE uvp.user_id = ? AND uvp.last_watched_at IS NOT NULL
            ) ranked
            WHERE rn = 1
            ORDER BY program_last_watched_at DESC
        ',
            [$user->id]
        );

        if ($latestRows === []) {
            return [];
        }

        $programIds = array_column($latestRows, 'program_id');
        $lessonIds = array_column($latestRows, 'lesson_id');
        $videoIds = array_column($latestRows, 'video_id');

        // Batch: progress + total duration per program
        $statsRows = DB::table('videos as v')
            ->join('lessons as l', 'l.id', '=', 'v.lesson_id')
            ->leftJoin('video_translations as vt', function ($join) {
                $join->on('vt.video_id', '=', 'v.id')
                    ->where('vt.locale', '=', config('app.locale'));
            })
            ->leftJoin('user_video_progress as uvp', function ($join) use ($user) {
                $join->on('uvp.video_id', '=', 'v.id')->where('uvp.user_id', '=', $user->id);
            })
            ->whereIn('l.program_id', $programIds)
            ->groupBy('l.program_id')
            ->selectRaw('
                l.program_id,
                COALESCE(SUM(COALESCE(uvp.watched_seconds, 0)), 0) AS watched_seconds,
                COALESCE(ROUND(COUNT(CASE WHEN uvp.is_completed THEN 1 END) * 100.0 / NULLIF(COUNT(v.id), 0)), 0) AS completed_percent,
                COALESCE(SUM(COALESCE(vt.duration_seconds, 0)), 0) AS total_duration_seconds
            ')
            ->get()
            ->keyBy('program_id');

        // Batch load models
        $programs = Program::withTranslation()->whereIn('id', $programIds)->get()->keyBy('id');
        $lessons = Lesson::withTranslation()->whereIn('id', $lessonIds)->get()->keyBy('id');
        $videos = Video::withTranslation()->whereIn('id', $videoIds)->get()->keyBy('id');

        return array_map(function ($row) use ($programs, $lessons, $videos, $statsRows) {
            $program = $programs[$row->program_id];
            $lesson = $lessons[$row->lesson_id];
            $video = $videos[$row->video_id];
            $stats = $statsRows[$row->program_id] ?? null;

            return [
                'id' => $program->id,
                'name' => $program->name,
                'cover' => $program->cover,
                'duration_seconds' => (int) ($stats?->total_duration_seconds ?? 0),
                'progress' => [
                    'watched_seconds' => (int) ($stats?->watched_seconds ?? 0),
                    'completed_percent' => (int) ($stats?->completed_percent ?? 0),
                ],
                'last_lesson' => [
                    'id' => $lesson->id,
                    'name' => $lesson->name,
                    'day' => $lesson->day,
                    'thumbnail' => $lesson->thumbnail,
                    'video' => [
                        'id' => $video->id,
                        'duration_seconds' => (int) $video->duration_seconds,
                    ],
                ],
            ];
        }, $latestRows);
    }

    /**
     * Batch progress for multiple lessons — one SQL query.
     *
     * @param  array<int>  $lessonIds
     * @return array<int, array{watched_seconds: int, completed_percent: int}>
     */
    public function lessonProgressMapForUser(User $user, array $lessonIds): array
    {
        if ($lessonIds === []) {
            return [];
        }

        $rows = DB::table('videos as v')
            ->leftJoin('user_video_progress as uvp', function ($join) use ($user) {
                $join->on('uvp.video_id', '=', 'v.id')->where('uvp.user_id', '=', $user->id);
            })
            ->whereIn('v.lesson_id', $lessonIds)
            ->groupBy('v.lesson_id')
            ->selectRaw('
                v.lesson_id,
                COALESCE(SUM(COALESCE(uvp.watched_seconds, 0)), 0) AS watched_seconds,
                COALESCE(ROUND(COUNT(CASE WHEN uvp.is_completed THEN 1 END) * 100.0 / NULLIF(COUNT(v.id), 0)), 0) AS completed_percent
            ')
            ->get();

        $default = ['watched_seconds' => 0, 'completed_percent' => 0];
        $map = array_fill_keys($lessonIds, $default);
        foreach ($rows as $row) {
            $map[$row->lesson_id] = [
                'watched_seconds' => (int) $row->watched_seconds,
                'completed_percent' => (int) $row->completed_percent,
            ];
        }

        return $map;
    }
}
