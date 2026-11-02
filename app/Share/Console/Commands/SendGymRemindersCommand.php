<?php

namespace App\Share\Console\Commands;

use App\Share\Services\Gym\NotificationService;
use Illuminate\Console\Command;

class SendGymRemindersCommand extends Command
{
    protected $signature = 'gym:send-reminders';

    protected $description = 'Gửi thông báo nhắc lịch tập và gói sắp hết hạn, không gửi lại thông báo đã gửi';

    public function handle(NotificationService $notificationService): int
    {
        $this->info('Đã gửi '.$notificationService->sendReminders().' thông báo.');

        return self::SUCCESS;
    }
}
