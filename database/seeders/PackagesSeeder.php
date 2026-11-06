<?php

namespace Database\Seeders;

use App\Share\Enums\PackageType;
use App\Share\Models\Package;
use Illuminate\Database\Seeder;

/**
 * Bảng giá Barre · Pilates · Yoga, chuyển từ mock `fitness-web/src/mocks/packages.ts`.
 */
class PackagesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->packages() as $package) {
            if (Package::query()->whereTranslation('name', $package['name'], 'vi')->exists()) {
                continue;
            }

            Package::query()->create([
                'package_type' => $package['type'],
                'price' => $package['price'],
                'duration_days' => $package['days'],
                'class_credits' => $package['credits'],
                'session_duration_minutes' => 60,
                'is_off_peak' => $package['off_peak'] ?? false,
                'base_people_max' => $package['base_people'] ?? 2,
                'vi' => [
                    'name' => $package['name'],
                    'description' => $package['description'],
                ],
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function packages(): array
    {
        return [
            ['name' => 'Drop-in (1 buổi)', 'type' => PackageType::DropIn, 'price' => 300000, 'days' => 30, 'credits' => 1, 'description' => 'Barre · Pilates · Yoga. Có thể chia sẻ credit theo điều khoản. Redlight Barre/Pilates +30.000đ/buổi.'],
            ['name' => '5 classes', 'type' => PackageType::ClassPack, 'price' => 1250000, 'days' => 67, 'credits' => 5, 'description' => '2 tháng + 7 ngày. Chia sẻ được với người thân/bạn bè. Gia hạn khi đi du lịch hoặc ốm (theo yêu cầu).'],
            ['name' => '10 classes', 'type' => PackageType::ClassPack, 'price' => 2100000, 'days' => 135, 'credits' => 10, 'description' => '4 tháng + 15 ngày. Chia sẻ credit. Redlight Barre/Pilates +30.000đ/buổi.'],
            ['name' => '20 classes', 'type' => PackageType::ClassPack, 'price' => 3600000, 'days' => 210, 'credits' => 20, 'description' => '6 tháng + 30 ngày. Chia sẻ credit. Redlight Barre/Pilates +30.000đ/buổi.'],
            ['name' => '50 classes', 'type' => PackageType::ClassPack, 'price' => 8000000, 'days' => 425, 'credits' => 50, 'description' => '1 năm + 60 ngày. Chia sẻ credit. Redlight Barre/Pilates +30.000đ/buổi.'],
            ['name' => 'Unlimited 1 tháng (full)', 'type' => PackageType::Unlimited, 'price' => 1800000, 'days' => 37, 'credits' => null, 'description' => 'Không giới hạn buổi + 7 ngày. Không chia sẻ. Redlight +30.000đ/buổi.'],
            ['name' => 'Unlimited 1 tháng (off-peak)', 'type' => PackageType::Unlimited, 'price' => 1400000, 'days' => 37, 'credits' => null, 'off_peak' => true, 'description' => 'Khung ≤8h & ≥23h. +7 ngày. Không chia sẻ. Redlight +30.000đ/buổi.'],
            ['name' => 'Unlimited 3 tháng', 'type' => PackageType::Unlimited, 'price' => 4950000, 'days' => 120, 'credits' => null, 'description' => 'Không giới hạn buổi + 30 ngày. Không chia sẻ. Gia hạn khi có chứng từ vắng hợp lý.'],
            ['name' => 'Unlimited 6 tháng', 'type' => PackageType::Unlimited, 'price' => 8400000, 'days' => 225, 'credits' => null, 'description' => 'Không giới hạn buổi + 45 ngày. Không chia sẻ. Redlight +30.000đ/buổi.'],
            ['name' => 'Intro 5 demo classes', 'type' => PackageType::IntroDemo, 'price' => 500000, 'days' => 30, 'credits' => 5, 'description' => '1 tháng. Mỗi loại lớp chỉ thử 1 lần. Dành cho học viên mới.'],
            ['name' => 'Private 1 buổi', 'type' => PackageType::PrivatePack, 'price' => 800000, 'days' => 30, 'credits' => 1, 'description' => '1–2 người/buổi, 60 phút. Từ 3 người +200.000đ/người. Redlight +30.000đ/buổi.'],
            ['name' => 'Private 10 buổi', 'type' => PackageType::PrivatePack, 'price' => 7000000, 'days' => 180, 'credits' => 10, 'description' => '6 tháng. 1–2 người/buổi. Từ 3 người +200.000đ/người. Admin xác nhận lịch.'],
            ['name' => 'Private 20 buổi', 'type' => PackageType::PrivatePack, 'price' => 12000000, 'days' => 365, 'credits' => 20, 'description' => '12 tháng. 1–2 người/buổi. Từ 3 người +200.000đ/người.'],
            ['name' => 'Private Group (6+ người)', 'type' => PackageType::PrivateGroup, 'price' => 1800000, 'days' => 30, 'credits' => 1, 'base_people' => 100, 'description' => '6–100 người, 60 phút. Giá cố định cho nhóm từ 6 người. Redlight +30.000đ/buổi.'],
        ];
    }
}
