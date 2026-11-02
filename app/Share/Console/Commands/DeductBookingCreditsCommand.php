<?php

namespace App\Share\Console\Commands;

use App\Share\Services\Gym\BookingService;
use Illuminate\Console\Command;

class DeductBookingCreditsCommand extends Command
{
    protected $signature = 'gym:deduct-credits';

    protected $description = 'Trừ buổi cho các booking gói thường đã qua giờ học, kể cả khi học viên vắng';

    public function handle(BookingService $bookingService): int
    {
        $this->info('Đã xử lý '.$bookingService->deductFinishedBookings().' booking.');

        return self::SUCCESS;
    }
}
