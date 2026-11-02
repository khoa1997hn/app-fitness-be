<?php

namespace Database\Seeders;

use App\Share\Models\Event;
use Illuminate\Database\Seeder;

/**
 * Sự kiện mẫu để demo, không chạy trên production.
 */
class EventsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $events = [
            ['Yoga Day', 'Ngày hội yoga ngoài trời cho mọi trình độ.', 14, 'Công viên Bạch Đằng', 0, 50],
            ['Workshop Pilates chuyên sâu', 'Làm chủ kỹ thuật core cùng huấn luyện viên.', 21, 'Sảnh A', 300000, 30],
            ['Retreat cuối tuần', 'Hai ngày thư giãn, thiền và sound healing.', 45, 'Cát Bà', 2500000, 15],
        ];

        foreach ($events as [$name, $description, $days, $location, $price, $tickets]) {
            if (Event::query()->whereTranslation('name', $name, 'vi')->exists()) {
                continue;
            }

            Event::query()->create([
                'event_date' => now()->addDays($days)->setTime(9, 0),
                'location' => $location,
                'ticket_price' => $price,
                'total_tickets' => $tickets,
                'vi' => ['name' => $name, 'description' => $description],
            ]);
        }
    }
}
