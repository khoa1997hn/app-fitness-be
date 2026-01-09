@extends('admin.layouts.app')

@section('title', 'Cài đặt Car Pickup')

@section('content')
<!-- BEGIN: Breadcrumb -->
<div class="mb-5">
    <ul class="m-0 p-0 list-none">
        <li class="inline-block relative top-[3px] text-base text-primary-500 font-Inter ">
            <a href="{{ route('admin.dashboard') }}">
                <iconify-icon icon="heroicons-outline:home"></iconify-icon>
                <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
            </a>
        </li>
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">
            Cài đặt Car Pickup
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->
<div class="space-y-5">
    <div class="card">
        <div class="card-body flex flex-col p-6">
            <header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5 -mx-6 px-6">
                <div class="flex-1">
                    <div class="card-title text-slate-900 dark:text-white">Cài đặt Car Pickup</div>
                </div>
            </header>
            <form action="{{ route('admin.carpickup.store') }}" method="POST" class="card-text h-full space-y-4 max-w-4xl">
                @csrf

                <div class="border-t border-slate-100 dark:border-slate-700 pt-4">
                    <h5 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Loại xe</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="input-area">
                            <label for="car_type_economy_4_seater" class="form-label">Economy 4 Seater ($)</label>
                            <input type="number" step="0.01" id="car_type_economy_4_seater" name="car_type[{{ $carPickupCarType::Economy4Seater }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('car_type.'.$carPickupCarType::Economy4Seater.'.price', $carType?->economy4Seater->price) }}" required>
                            @error('car_type.'.$carPickupCarType::Economy4Seater.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-area">
                            <label for="car_type_economy_7_seater" class="form-label">Economy 7 Seater ($)</label>
                            <input type="number" step="0.01" id="car_type_economy_7_seater" name="car_type[{{ $carPickupCarType::Economy7Seater }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('car_type.'.$carPickupCarType::Economy7Seater.'.price', $carType?->economy7Seater->price) }}" required>
                            @error('car_type.'.$carPickupCarType::Economy7Seater.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-area">
                            <label for="car_type_economy_16_seater" class="form-label">Economy 16 Seater ($)</label>
                            <input type="number" step="0.01" id="car_type_economy_16_seater" name="car_type[{{ $carPickupCarType::Economy16Seater }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('car_type.'.$carPickupCarType::Economy16Seater.'.price', $carType?->economy16Seater->price) }}" required>
                            @error('car_type.'.$carPickupCarType::Economy16Seater.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-area">
                            <label for="car_type_elegant_4_seater" class="form-label">Elegant 4 Seater ($)</label>
                            <input type="number" step="0.01" id="car_type_elegant_4_seater" name="car_type[{{ $carPickupCarType::Elegant4Seater }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('car_type.'.$carPickupCarType::Elegant4Seater.'.price', $carType?->elegant4Seater->price) }}" required>
                            @error('car_type.'.$carPickupCarType::Elegant4Seater.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-area">
                            <label for="car_type_elegant_7_seater" class="form-label">Elegant 7 Seater ($)</label>
                            <input type="number" step="0.01" id="car_type_elegant_7_seater" name="car_type[{{ $carPickupCarType::Elegant7Seater }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('car_type.'.$carPickupCarType::Elegant7Seater.'.price', $carType?->elegant7Seater->price) }}" required>
                            @error('car_type.'.$carPickupCarType::Elegant7Seater.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-area">
                            <label for="car_type_elegant_16_seater" class="form-label">Elegant 16 Seater ($)</label>
                            <input type="number" step="0.01" id="car_type_elegant_16_seater" name="car_type[{{ $carPickupCarType::Elegant16Seater }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('car_type.'.$carPickupCarType::Elegant16Seater.'.price', $carType?->elegant16Seater->price) }}" required>
                            @error('car_type.'.$carPickupCarType::Elegant16Seater.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="submit" class="btn inline-flex justify-center btn-primary px-6">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
