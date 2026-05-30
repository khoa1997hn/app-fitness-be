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
