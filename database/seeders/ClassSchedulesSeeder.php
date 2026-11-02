<?php

namespace Database\Seeders;

use App\Share\Enums\ClassScheduleStatus;
use App\Share\Enums\Role;
use App\Share\Models\ClassSchedule;
use App\Share\Models\FitnessClass;
use App\Share\Models\User;
use Illuminate\Database\Seeder;

/**
 * Thời khóa biểu mẫu 14 ngày tới, gồm khung off-peak. Dữ liệu demo, cần chạy sau
 * GymDemoUsersSeeder và FitnessClassesSeeder.
 */
class ClassSchedulesSeeder extends Seeder
{
    private const DAYS = 14;

    private const START_HOURS = ['07:00', '09:00', '12:00', '18:00', '23:00'];

    private const ROOMS = ['P101', 'P102', 'P202', 'P301'];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trainer = User::query()->where('role', Role::Trainer)->first();
        $classes = FitnessClass::query()->orderBy('id')->get();

        if (! $trainer || $classes->isEmpty() || ClassSchedule::query()->where('start_time', '>', now())->exists()) {
            return;
        }

        $index = 0;

        for ($day = 1; $day <= self::DAYS; $day++) {
            foreach (self::START_HOURS as $hour) {
                $start = today()->addDays($day)->setTimeFromTimeString($hour);

                ClassSchedule::query()->create([
                    'fitness_class_id' => $classes[$index % $classes->count()]->id,
                    'trainer_id' => $trainer->id,
                    'room' => self::ROOMS[$index % count(self::ROOMS)],
                    'start_time' => $start,
                    'end_time' => $start->copy()->addHour(),
                    'max_slot' => 15,
                    'status' => ClassScheduleStatus::Active,
                ]);

                $index++;
            }
        }
    }
}
