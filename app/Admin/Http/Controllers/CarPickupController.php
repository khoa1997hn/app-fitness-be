<?php

namespace App\Admin\Http\Controllers;

use App\Share\Enums\CarPickupCarType;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Services\Setting\CarPickup\CarPickupCarType as CarPickupCarTypeDTO;
use App\Share\Services\Setting\CarPickup\CarPickupCarTypeOption;
use App\Share\Services\Setting\CarPickup\CarPickupSettingService;
use Illuminate\Http\Request;

class CarPickupController extends BaseController
{
    public function __construct(
        private readonly CarPickupSettingService $settingService
    ) {}

    public function index()
    {
        $carTypeSetting = $this->settingService->getCarType();

        $carType = null;
        if ($carTypeSetting) {
            $carType = CarPickupCarTypeDTO::fromJson($carTypeSetting);
        }

        return view('admin.carpickup.index', [
            'carType' => $carType,
            'carPickupCarType' => CarPickupCarType::class,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_type.'.CarPickupCarType::Economy4Seater.'.price' => 'required|numeric|min:0',
            'car_type.'.CarPickupCarType::Economy7Seater.'.price' => 'required|numeric|min:0',
            'car_type.'.CarPickupCarType::Economy16Seater.'.price' => 'required|numeric|min:0',
            'car_type.'.CarPickupCarType::Elegant4Seater.'.price' => 'required|numeric|min:0',
            'car_type.'.CarPickupCarType::Elegant7Seater.'.price' => 'required|numeric|min:0',
            'car_type.'.CarPickupCarType::Elegant16Seater.'.price' => 'required|numeric|min:0',
        ], [
            'car_type.*.price.required' => 'Giá không được để trống.',
            'car_type.*.price.numeric' => 'Giá phải là số.',
            'car_type.*.price.min' => 'Giá phải lớn hơn hoặc bằng 0.',
        ]);

        $carType = new CarPickupCarTypeDTO(
            economy4Seater: new CarPickupCarTypeOption((float) $validated['car_type'][CarPickupCarType::Economy4Seater]['price']),
            economy7Seater: new CarPickupCarTypeOption((float) $validated['car_type'][CarPickupCarType::Economy7Seater]['price']),
            economy16Seater: new CarPickupCarTypeOption((float) $validated['car_type'][CarPickupCarType::Economy16Seater]['price']),
            elegant4Seater: new CarPickupCarTypeOption((float) $validated['car_type'][CarPickupCarType::Elegant4Seater]['price']),
            elegant7Seater: new CarPickupCarTypeOption((float) $validated['car_type'][CarPickupCarType::Elegant7Seater]['price']),
            elegant16Seater: new CarPickupCarTypeOption((float) $validated['car_type'][CarPickupCarType::Elegant16Seater]['price']),
        );
        $this->settingService->setCarType($carType);

        return redirect()->route('admin.carpickup.index')->with('success', 'Cập nhật cài đặt Car Pickup thành công.');
    }
}
