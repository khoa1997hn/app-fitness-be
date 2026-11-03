<?php

namespace App\Share\Console\Commands;

use App\Share\Services\Gym\RedlightService;
use Illuminate\Console\Command;

class CancelFinishedRedlightOrdersCommand extends Command
{
    protected $signature = 'gym:cancel-finished-redlight-orders';

    protected $description = 'Hủy đơn Redlight chờ thanh toán của các lớp đã qua giờ';

    public function handle(RedlightService $redlightService): int
    {
        $this->info('Đã hủy '.$redlightService->cancelOrdersOfFinishedClasses().' đơn.');

        return self::SUCCESS;
    }
}
