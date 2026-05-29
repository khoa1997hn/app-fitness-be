<?php

namespace Database\Seeders;

use App\Share\Models\Lesson;
use App\Share\Models\User;
use Illuminate\Database\Seeder;

class LessonFavoritesSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        if (! $user) {
            return;
        }

        $lessonIds = Lesson::query()->take(3)->pluck('id')->all();

        $user->favoriteLessons()->syncWithoutDetaching($lessonIds);
    }
}
