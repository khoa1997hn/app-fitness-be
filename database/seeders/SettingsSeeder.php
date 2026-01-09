<?php

namespace Database\Seeders;

use App\Share\Services\Setting\CarPickup\CarPickupCarType as CarPickupCarTypeDTO;
use App\Share\Services\Setting\CarPickup\CarPickupCarTypeOption;
use App\Share\Services\Setting\CarPickup\CarPickupSettingService;
use App\Share\Services\Setting\ESim\ESimSettingService;
use App\Share\Services\Setting\ESim\ESimType as ESimTypeDTO;
use App\Share\Services\Setting\ESim\ESimTypeOption;
use App\Share\Services\Setting\EVisa\EVisaSettingService;
use App\Share\Services\Setting\EVisa\LengthOfVisa;
use App\Share\Services\Setting\EVisa\LengthOfVisaOption;
use App\Share\Services\Setting\EVisa\ProcessingTime;
use App\Share\Services\Setting\EVisa\ProcessingTimeOption;
use App\Share\Services\Setting\FastTrack\FastTrackSettingService;
use App\Share\Services\Setting\FastTrack\FastTrackType as FastTrackTypeDTO;
use App\Share\Services\Setting\FastTrack\FastTrackTypeOption;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $evisaService = app(EVisaSettingService::class);
        $fastTrackService = app(FastTrackSettingService::class);
        $carPickupService = app(CarPickupSettingService::class);
        $eSimService = app(ESimSettingService::class);

        // EVisa - Length of Visa
        $lengthOfVisa = new LengthOfVisa(
            oneMonthSingle: new LengthOfVisaOption(price: 44.0, adminAndGovernmentFee: 35.0),
            oneMonthMultiple: new LengthOfVisaOption(price: 49.0, adminAndGovernmentFee: 60.0),
            threeMonthSingle: new LengthOfVisaOption(price: 54.0, adminAndGovernmentFee: 35.0),
            threeMonthMultiple: new LengthOfVisaOption(price: 59.0, adminAndGovernmentFee: 60.0),
        );
        $evisaService->setLengthOfVisa($lengthOfVisa);

        // EVisa - Processing Time
        $processingTime = new ProcessingTime(
            normal: new ProcessingTimeOption(price: 0.0),
            urgent: new ProcessingTimeOption(price: 40.0),
            superUrgent: new ProcessingTimeOption(price: 80.0),
            express: new ProcessingTimeOption(price: 120.0),
            emergency: new ProcessingTimeOption(price: 200.0),
            weekendOrHoliday: new ProcessingTimeOption(price: 355.0),
        );
        $evisaService->setProcessingTime($processingTime);

        // FastTrack - Type
        $fastTrackType = new FastTrackTypeDTO(
            normal: new FastTrackTypeOption(price: 39.0),
            vipB: new FastTrackTypeOption(price: 79.0),
        );
        $fastTrackService->setType($fastTrackType);

        // CarPickup - Car Type
        $carPickupCarType = new CarPickupCarTypeDTO(
            economy4Seater: new CarPickupCarTypeOption(price: 29.0),
            economy7Seater: new CarPickupCarTypeOption(price: 29.0),
            economy16Seater: new CarPickupCarTypeOption(price: 45.0),
            elegant4Seater: new CarPickupCarTypeOption(price: 99.0),
            elegant7Seater: new CarPickupCarTypeOption(price: 99.0),
            elegant16Seater: new CarPickupCarTypeOption(price: 135.0),
        );
        $carPickupService->setCarType($carPickupCarType);

        // ESim - Type
        $eSimType = new ESimTypeDTO(
            viettel: new ESimTypeOption(price: 29.0),
            mobifone: new ESimTypeOption(price: 29.0),
            vinaphone: new ESimTypeOption(price: 29.0),
        );
        $eSimService->setType($eSimType);
    }
}
