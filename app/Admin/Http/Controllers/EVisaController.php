<?php

namespace App\Admin\Http\Controllers;

use App\Share\Enums\EVisaLength;
use App\Share\Enums\EVisaProcessingTime;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Services\Setting\EVisa\EVisaSettingService;
use App\Share\Services\Setting\EVisa\LengthOfVisa;
use App\Share\Services\Setting\EVisa\LengthOfVisaOption;
use App\Share\Services\Setting\EVisa\ProcessingTime;
use App\Share\Services\Setting\EVisa\ProcessingTimeOption;
use Illuminate\Http\Request;

class EVisaController extends BaseController
{
    public function __construct(
        private readonly EVisaSettingService $settingService
    ) {}

    public function index()
    {
        $lengthOfVisaSetting = $this->settingService->getLengthOfVisa();
        $lengthOfVisa = null;
        if ($lengthOfVisaSetting) {
            $lengthOfVisa = LengthOfVisa::fromJson($lengthOfVisaSetting);
        }

        $processingTimeSetting = $this->settingService->getProcessingTime();
        $processingTime = null;
        if ($processingTimeSetting) {
            $processingTime = ProcessingTime::fromJson($processingTimeSetting);
        }

        return view('admin.evisa.index', [
            'lengthOfVisa' => $lengthOfVisa,
            'processingTime' => $processingTime,
            'evisaLength' => EVisaLength::class,
            'evisaProcessingTime' => EVisaProcessingTime::class,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'length_of_visa.'.EVisaLength::OneMonthSingle.'.price' => 'required|numeric|min:0',
            'length_of_visa.'.EVisaLength::OneMonthSingle.'.admin_and_government_fee' => 'nullable|numeric|min:0',
            'length_of_visa.'.EVisaLength::OneMonthMultiple.'.price' => 'required|numeric|min:0',
            'length_of_visa.'.EVisaLength::OneMonthMultiple.'.admin_and_government_fee' => 'nullable|numeric|min:0',
            'length_of_visa.'.EVisaLength::ThreeMonthSingle.'.price' => 'required|numeric|min:0',
            'length_of_visa.'.EVisaLength::ThreeMonthSingle.'.admin_and_government_fee' => 'nullable|numeric|min:0',
            'length_of_visa.'.EVisaLength::ThreeMonthMultiple.'.price' => 'required|numeric|min:0',
            'length_of_visa.'.EVisaLength::ThreeMonthMultiple.'.admin_and_government_fee' => 'nullable|numeric|min:0',
            'processing_time.'.EVisaProcessingTime::Normal.'.price' => 'required|numeric|min:0',
            'processing_time.'.EVisaProcessingTime::Urgent.'.price' => 'required|numeric|min:0',
            'processing_time.'.EVisaProcessingTime::SuperUrgent.'.price' => 'required|numeric|min:0',
            'processing_time.'.EVisaProcessingTime::Express.'.price' => 'required|numeric|min:0',
            'processing_time.'.EVisaProcessingTime::Emergency.'.price' => 'required|numeric|min:0',
            'processing_time.'.EVisaProcessingTime::WeekendOrHoliday.'.price' => 'required|numeric|min:0',
        ], [
            'length_of_visa.*.price.required' => 'Giá không được để trống.',
            'length_of_visa.*.price.numeric' => 'Giá phải là số.',
            'length_of_visa.*.price.min' => 'Giá phải lớn hơn hoặc bằng 0.',
            'length_of_visa.*.admin_and_government_fee.numeric' => 'Phí admin và chính phủ phải là số.',
            'length_of_visa.*.admin_and_government_fee.min' => 'Phí admin và chính phủ phải lớn hơn hoặc bằng 0.',
            'processing_time.*.price.required' => 'Giá không được để trống.',
            'processing_time.*.price.numeric' => 'Giá phải là số.',
            'processing_time.*.price.min' => 'Giá phải lớn hơn hoặc bằng 0.',
        ]);

        $lengthOfVisa = new LengthOfVisa(
            oneMonthSingle: new LengthOfVisaOption(
                price: (float) $validated['length_of_visa'][EVisaLength::OneMonthSingle]['price'],
                adminAndGovernmentFee: isset($validated['length_of_visa'][EVisaLength::OneMonthSingle]['admin_and_government_fee']) ? (float) $validated['length_of_visa'][EVisaLength::OneMonthSingle]['admin_and_government_fee'] : null,
            ),
            oneMonthMultiple: new LengthOfVisaOption(
                price: (float) $validated['length_of_visa'][EVisaLength::OneMonthMultiple]['price'],
                adminAndGovernmentFee: isset($validated['length_of_visa'][EVisaLength::OneMonthMultiple]['admin_and_government_fee']) ? (float) $validated['length_of_visa'][EVisaLength::OneMonthMultiple]['admin_and_government_fee'] : null,
            ),
            threeMonthSingle: new LengthOfVisaOption(
                price: (float) $validated['length_of_visa'][EVisaLength::ThreeMonthSingle]['price'],
                adminAndGovernmentFee: isset($validated['length_of_visa'][EVisaLength::ThreeMonthSingle]['admin_and_government_fee']) ? (float) $validated['length_of_visa'][EVisaLength::ThreeMonthSingle]['admin_and_government_fee'] : null,
            ),
            threeMonthMultiple: new LengthOfVisaOption(
                price: (float) $validated['length_of_visa'][EVisaLength::ThreeMonthMultiple]['price'],
                adminAndGovernmentFee: isset($validated['length_of_visa'][EVisaLength::ThreeMonthMultiple]['admin_and_government_fee']) ? (float) $validated['length_of_visa'][EVisaLength::ThreeMonthMultiple]['admin_and_government_fee'] : null,
            ),
        );
        $this->settingService->setLengthOfVisa($lengthOfVisa);

        $processingTime = new ProcessingTime(
            normal: new ProcessingTimeOption((float) $validated['processing_time'][EVisaProcessingTime::Normal]['price']),
            urgent: new ProcessingTimeOption((float) $validated['processing_time'][EVisaProcessingTime::Urgent]['price']),
            superUrgent: new ProcessingTimeOption((float) $validated['processing_time'][EVisaProcessingTime::SuperUrgent]['price']),
            express: new ProcessingTimeOption((float) $validated['processing_time'][EVisaProcessingTime::Express]['price']),
            emergency: new ProcessingTimeOption((float) $validated['processing_time'][EVisaProcessingTime::Emergency]['price']),
            weekendOrHoliday: new ProcessingTimeOption((float) $validated['processing_time'][EVisaProcessingTime::WeekendOrHoliday]['price']),
        );
        $this->settingService->setProcessingTime($processingTime);

        return redirect()->route('admin.evisa.index')->with('success', 'Cập nhật cài đặt E-Visa thành công.');
    }
}
