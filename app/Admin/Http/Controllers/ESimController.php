<?php

namespace App\Admin\Http\Controllers;

use App\Share\Enums\ESimType;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Services\Setting\ESim\ESimSettingService;
use App\Share\Services\Setting\ESim\ESimType as ESimTypeDTO;
use App\Share\Services\Setting\ESim\ESimTypeOption;
use Illuminate\Http\Request;

class ESimController extends BaseController
{
    public function __construct(
        private readonly ESimSettingService $settingService
    ) {}

    public function index()
    {
        $typeSetting = $this->settingService->getType();

        $type = null;
        if ($typeSetting) {
            $type = ESimTypeDTO::fromJson($typeSetting);
        }

        return view('admin.esim.index', [
            'type' => $type,
            'eSimType' => ESimType::class,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type.'.ESimType::Viettel.'.price' => 'required|numeric|min:0',
            'type.'.ESimType::Mobifone.'.price' => 'required|numeric|min:0',
            'type.'.ESimType::Vinaphone.'.price' => 'required|numeric|min:0',
        ], [
            'type.*.price.required' => 'Giá không được để trống.',
            'type.*.price.numeric' => 'Giá phải là số.',
            'type.*.price.min' => 'Giá phải lớn hơn hoặc bằng 0.',
        ]);

        $type = new ESimTypeDTO(
            viettel: new ESimTypeOption((float) $validated['type'][ESimType::Viettel]['price']),
            mobifone: new ESimTypeOption((float) $validated['type'][ESimType::Mobifone]['price']),
            vinaphone: new ESimTypeOption((float) $validated['type'][ESimType::Vinaphone]['price']),
        );
        $this->settingService->setType($type);

        return redirect()->route('admin.esim.index')->with('success', 'Cập nhật cài đặt E-Sim thành công.');
    }
}
