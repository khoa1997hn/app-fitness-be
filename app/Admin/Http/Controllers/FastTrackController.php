<?php

namespace App\Admin\Http\Controllers;

use App\Share\Enums\FastTrackType;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Services\Setting\FastTrack\FastTrackSettingService;
use App\Share\Services\Setting\FastTrack\FastTrackType as FastTrackTypeDTO;
use App\Share\Services\Setting\FastTrack\FastTrackTypeOption;
use Illuminate\Http\Request;

class FastTrackController extends BaseController
{
    public function __construct(
        private readonly FastTrackSettingService $settingService
    ) {}

    public function index()
    {
        $typeSetting = $this->settingService->getType();

        $type = null;
        if ($typeSetting) {
            $type = FastTrackTypeDTO::fromJson($typeSetting);
        }

        return view('admin.fasttrack.index', [
            'type' => $type,
            'fastTrackType' => FastTrackType::class,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type.'.FastTrackType::Normal.'.price' => 'required|numeric|min:0',
            'type.'.FastTrackType::VipB.'.price' => 'required|numeric|min:0',
        ], [
            'type.*.price.required' => 'Giá không được để trống.',
            'type.*.price.numeric' => 'Giá phải là số.',
            'type.*.price.min' => 'Giá phải lớn hơn hoặc bằng 0.',
        ]);

        $type = new FastTrackTypeDTO(
            normal: new FastTrackTypeOption((float) $validated['type'][FastTrackType::Normal]['price']),
            vipB: new FastTrackTypeOption((float) $validated['type'][FastTrackType::VipB]['price']),
        );
        $this->settingService->setType($type);

        return redirect()->route('admin.fasttrack.index')->with('success', 'Cập nhật cài đặt Fast Track thành công.');
    }
}
