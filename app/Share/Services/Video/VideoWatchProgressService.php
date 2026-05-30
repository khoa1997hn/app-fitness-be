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
     * Record progress and return video + lesson + program watched_percent.
     *
     * @return array{video: array<string, mixed>, lesson: array<string, mixed>, program: array<string, mixed>}
     */
    public function record(User $user, Video $video, int $watchedPercent): array
    {
        $watchedPercent = max(0, min(100, $watchedPercent));

        $progress = UserVideoProgress::query()->firstOrNew([
            'user_id' => $user->id,
            'video_id' => $video->id,
        ]);

        $progress->watched_percent = max((int) ($progress->watched_percent ?? 0), $watchedPercent);
        $progress->save();

        $video->loadMissing(['lesson.program']);
        $lesson = $video->lesson;
        $program = $lesson->program;

        return [
            'video' => [
                'id' => $video->id,
                'lesson_id' => $video->lesson_id,
                'duration_seconds' => (int) $video->duration_seconds,
                'watched_percent' => $progress->watched_percent,
            ],
            'lesson' => [
                'id' => $lesson->id,
                'watched_percent' => $this->lessonWatchedPercent($user, $lesson),
            ],
            'program' => [
                'id' => $program->id,
                'watched_percent' => $this->programWatchedPercent($user, $program),
            ],
        ];
    }

    /**
     * Weighted watched percent for a video.
     */
    public function videoWatchedPercent(User $user, Video $video): int
    {
        return (int) (UserVideoProgress::query()
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->value('watched_percent') ?? 0);
    }

    /**
     * Weighted watched percent for a lesson — calculated via SQL (no collection loading).
     */
    public function lessonWatchedPercent(User $user, Lesson $lesson): int
    {
        $result = DB::table('videos as v')
            ->leftJoin('user_video_progress as uvp', function ($join) use ($user) {
                $join->on('uvp.video_id', '=', 'v.id')->where('uvp.user_id', '=', $user->id);
            })
            ->where('v.lesson_id', $lesson->id)
            ->where('v.duration_seconds', '>', 0)
            ->selectRaw('COALESCE(ROUND(SUM(COALESCE(uvp.watched_percent, 0) * v.duration_seconds) / NULLIF(SUM(v.duration_seconds), 0)), 0) AS watched_percent')
            ->value('watched_percent');

        return (int) ($result ?? 0);
    }

    /**
     * Weighted watched percent for a program — calculated via SQL (no collection loading).
     */
    public function programWatchedPercent(User $user, Program $program): int
    {
        $result = DB::table('videos as v')
            ->join('lessons as l', 'l.id', '=', 'v.lesson_id')
            ->leftJoin('user_video_progress as uvp', function ($join) use ($user) {
                $join->on('uvp.video_id', '=', 'v.id')->where('uvp.user_id', '=', $user->id);
            })
            ->where('l.program_id', $program->id)
            ->where('v.duration_seconds', '>', 0)
            ->selectRaw('COALESCE(ROUND(SUM(COALESCE(uvp.watched_percent, 0) * v.duration_seconds) / NULLIF(SUM(v.duration_seconds), 0)), 0) AS watched_percent')
            ->value('watched_percent');

        return (int) ($result ?? 0);
    }

    /**
     * Batch watched percent for multiple programs — one SQL query.
     *
     * @param  array<int>  $programIds
     * @return array<int, int> programId => watched_percent
     */
    public function programPercentMapForUser(User $user, array $programIds): array
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
            ->where('v.duration_seconds', '>', 0)
            ->groupBy('l.program_id')
            ->selectRaw('l.program_id, COALESCE(ROUND(SUM(COALESCE(uvp.watched_percent, 0) * v.duration_seconds) / NULLIF(SUM(v.duration_seconds), 0)), 0) AS watched_percent')
            ->get();

        $map = array_fill_keys($programIds, 0);
        foreach ($rows as $row) {
            $map[$row->program_id] = (int) $row->watched_percent;
        }

        return $map;
    }

    /**
     * Batch watched percent for multiple lessons — one SQL query.
     *
     * @param  array<int>  $lessonIds
     * @return array<int, int> lessonId => watched_percent
     */
    public function lessonPercentMapForUser(User $user, array $lessonIds): array
    {
        if ($lessonIds === []) {
            return [];
        }

        $rows = DB::table('videos as v')
            ->leftJoin('user_video_progress as uvp', function ($join) use ($user) {
                $join->on('uvp.video_id', '=', 'v.id')->where('uvp.user_id', '=', $user->id);
            })
            ->whereIn('v.lesson_id', $lessonIds)
            ->where('v.duration_seconds', '>', 0)
            ->groupBy('v.lesson_id')
            ->selectRaw('v.lesson_id, COALESCE(ROUND(SUM(COALESCE(uvp.watched_percent, 0) * v.duration_seconds) / NULLIF(SUM(v.duration_seconds), 0)), 0) AS watched_percent')
            ->get();

        $map = array_fill_keys($lessonIds, 0);
        foreach ($rows as $row) {
            $map[$row->lesson_id] = (int) $row->watched_percent;
        }

        return $map;
    }
}
