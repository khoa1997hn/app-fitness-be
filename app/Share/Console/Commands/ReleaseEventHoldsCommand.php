<?php

namespace App\Share\Console\Commands;

use App\Share\Services\Gym\EventService;
use Illuminate\Console\Command;

class ReleaseEventHoldsCommand extends Command
{
    protected $signature = 'gym:release-event-holds';

    protected $description = 'Nhả vé sự kiện đã quá hạn giữ chỗ và hủy đơn hàng liên quan';

    public function handle(EventService $eventService): int
    {
        $this->info('Đã nhả '.$eventService->releaseExpiredHolds().' lượt giữ vé.');

        return self::SUCCESS;
    }
}
