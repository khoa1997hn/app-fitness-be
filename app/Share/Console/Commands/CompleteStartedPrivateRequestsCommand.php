<?php

namespace App\Share\Console\Commands;

use App\Share\Services\Gym\PrivateRequestService;
use Illuminate\Console\Command;

class CompleteStartedPrivateRequestsCommand extends Command
{
    protected $signature = 'gym:complete-started-private-requests';

    protected $description = 'Tự hủy đơn Private chờ duyệt đã quá giờ (hoàn buổi) và tự hoàn thành các buổi đã duyệt đã qua giờ bắt đầu';

    public function handle(PrivateRequestService $privateRequestService): int
    {
        $this->info('Đã tự hủy '.$privateRequestService->cancelExpiredPending().' đơn Private quá giờ chưa duyệt.');
        $this->info('Đã hoàn thành '.$privateRequestService->completeStarted().' buổi Private.');

        return self::SUCCESS;
    }
}
