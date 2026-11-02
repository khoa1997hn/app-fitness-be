<?php

namespace Database\Seeders;

use App\Share\Models\TherapyService;
use Illuminate\Database\Seeder;

/**
 * 6 dịch vụ Recovery, chuyển từ mock `fitness-web/src/mocks/therapyServices.ts`.
 */
class TherapyServicesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->services() as $service) {
            if (TherapyService::query()->where('slug', $service['slug'])->exists()) {
                continue;
            }

            TherapyService::query()->create([
                'slug' => $service['slug'],
                'single_price' => $service['single_price'],
                'single_duration_minutes' => $service['duration'],
                'combo5_price' => $service['combo5_price'],
                'vi' => [
                    'name' => $service['name'],
                    'long_description' => $service['description'],
                    'benefits' => $service['benefits'],
                ],
            ]);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function services(): array
    {
        return [
            ['slug' => 'deep-stretch', 'name' => 'Deep Stretch', 'single_price' => 500000, 'duration' => 60, 'combo5_price' => 2000000, 'description' => 'Buổi Deep Stretch giúp giải phóng căng cơ sâu, cải thiện phạm vi chuyển động và hỗ trợ phục hồi sau tập luyện cường độ cao.', 'benefits' => ['Giảm cứng cơ và đau nhức vùng lưng, hông, vai', 'Tăng độ linh hoạt an toàn với hướng dẫn chuyên viên', 'Phù hợp sau lớp Barre/Pilates hoặc ngày tập nặng']],
            ['slug' => 'deep-muscle-massage', 'name' => 'Deep Muscle Massage', 'single_price' => 600000, 'duration' => 60, 'combo5_price' => 2500000, 'description' => 'Massage cơ sâu kết hợp kỹ thuật trị liệu mô mềm, tập trung vùng cơ bị siết và mỏi do tư thế hoặc tập luyện.', 'benefits' => ['Giảm căng cơ sâu, hỗ trợ tuần hoàn', 'Cá nhân hóa vùng cần tập trung trong buổi', 'Combo 5 buổi tiết kiệm cho liệu trình phục hồi dài hạn']],
            ['slug' => 'prive-sound-healing', 'name' => 'Prive Relaxation Sound Healing', 'single_price' => 500000, 'duration' => 60, 'combo5_price' => 2000000, 'description' => 'Trải nghiệm thư giãn sâu với liệu pháp âm thanh trong không gian riêng tư, giúp giảm stress và cân bằng thần kinh.', 'benefits' => ['Giảm căng thẳng, hỗ trợ giấc ngủ', 'Không gian riêng, trải nghiệm tĩnh lặng', 'Phù hợp nghỉ ngơi chủ động giữa các chu kỳ tập']],
            ['slug' => 'infrares-sauna', 'name' => 'Infrares Sauna', 'single_price' => 300000, 'duration' => 30, 'combo5_price' => 1000000, 'description' => 'Xông hơi hồng ngoại hỗ trợ thải độc nhẹ, ấm cơ và thư giãn sau buổi tập — thời lượng ngắn, hiệu quả phục hồi nhanh.', 'benefits' => ['Ấm cơ, hỗ trợ lưu thông', 'Buổi 30 phút — dễ kết hợp trong ngày', 'Combo 5 buổi phù hợp liệu trình định kỳ']],
            ['slug' => 'redlight-therapy', 'name' => 'Redlight Therapy', 'single_price' => 300000, 'duration' => 30, 'combo5_price' => 1000000, 'description' => 'Liệu pháp ánh sáng đỏ hỗ trợ phục hồi mô, giảm viêm nhẹ và tăng cảm giác thư giãn sau tập.', 'benefits' => ['Hỗ trợ phục hồi cơ bắp', 'Phi-invasive, thư giãn trong 30 phút', 'Có thể mua lẻ hoặc combo tiết kiệm']],
            ['slug' => 'lymphatic-massage', 'name' => 'Lymphatic Massage', 'single_price' => 500000, 'duration' => 60, 'combo5_price' => 2000000, 'description' => 'Massage hệ bạch huyết nhẹ nhàng giúp giảm phù nề, hỗ trợ thải độc và cảm giác nhẹ cơ thể sau tuần tập dày.', 'benefits' => ['Giảm cảm giác nặng chân, phù nhẹ', 'Kỹ thuật nhẹ, phù hợp phục hồi', 'Combo 5 buổi cho chu trình drainage định kỳ']],
        ];
    }
}
