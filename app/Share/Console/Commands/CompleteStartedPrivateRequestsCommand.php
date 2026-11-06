<?php

namespace App\Share\Console\Commands;

use App\Share\Services\Gym\PrivateRequestService;
use Illuminate\Console\Command;

class CompleteStartedPrivateRequestsCommand extends Command
{
    protected $signature = 'gym:complete-started-private-requests';

    protected $description = 'Tự xác nhận hoàn thành các buổi Private đã duyệt và đã qua giờ bắt đầu';

    public function handle(PrivateRequestService $privateRequestService): int
    {
        $this->info('Đã hoàn thành '.$privateRequestService->completeStarted().' buổi Private.');

        return self::SUCCESS;
    }
}
