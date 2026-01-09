@extends('admin.layouts.app')

@section('title', 'Cài đặt E-Visa')

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
            Cài đặt E-Visa
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->
<div class="space-y-5">
    <div class="card">
        <div class="card-body flex flex-col p-6">
            <header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5 -mx-6 px-6">
                <div class="flex-1">
                    <div class="card-title text-slate-900 dark:text-white">Cài đặt E-Visa</div>
                </div>
            </header>
            <form action="{{ route('admin.evisa.store') }}" method="POST" class="card-text h-full space-y-4 max-w-4xl">
                @csrf

                <div class="border-t border-slate-100 dark:border-slate-700 pt-4">
                    <h5 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Thời hạn visa</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                        <div class="space-y-4">
                            <div class="input-area">
                                <label for="length_of_visa_1_month_single_price" class="form-label">1 tháng - Single Entry - Giá ($)</label>
                                <input type="number" step="0.01" id="length_of_visa_1_month_single_price" name="length_of_visa[{{ $evisaLength::OneMonthSingle }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('length_of_visa.'.$evisaLength::OneMonthSingle.'.price', $lengthOfVisa?->oneMonthSingle->price) }}" required>
                                @error('length_of_visa.'.$evisaLength::OneMonthSingle.'.price')
                                    <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="input-area">
                                <label for="length_of_visa_1_month_single_fee" class="form-label">1 tháng - Single Entry - Phí admin và chính phủ ($)</label>
                                <input type="number" step="0.01" id="length_of_visa_1_month_single_fee" name="length_of_visa[{{ $evisaLength::OneMonthSingle }}][admin_and_government_fee]" class="form-control" placeholder="Nhập phí admin và chính phủ ($)" value="{{ old('length_of_visa.'.$evisaLength::OneMonthSingle.'.admin_and_government_fee', $lengthOfVisa?->oneMonthSingle->adminAndGovernmentFee) }}" min="0">
                                @error('length_of_visa.'.$evisaLength::OneMonthSingle.'.admin_and_government_fee')
                                    <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="input-area">
                                <label for="length_of_visa_1_month_multiple_price" class="form-label">1 tháng - Multiple Entry - Giá ($)</label>
                                <input type="number" step="0.01" id="length_of_visa_1_month_multiple_price" name="length_of_visa[{{ $evisaLength::OneMonthMultiple }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('length_of_visa.'.$evisaLength::OneMonthMultiple.'.price', $lengthOfVisa?->oneMonthMultiple->price) }}" required>
                                @error('length_of_visa.'.$evisaLength::OneMonthMultiple.'.price')
                                    <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="input-area">
                                <label for="length_of_visa_1_month_multiple_fee" class="form-label">1 tháng - Multiple Entry - Phí admin và chính phủ ($)</label>
                                <input type="number" step="0.01" id="length_of_visa_1_month_multiple_fee" name="length_of_visa[{{ $evisaLength::OneMonthMultiple }}][admin_and_government_fee]" class="form-control" placeholder="Nhập phí admin và chính phủ ($)" value="{{ old('length_of_visa.'.$evisaLength::OneMonthMultiple.'.admin_and_government_fee', $lengthOfVisa?->oneMonthMultiple->adminAndGovernmentFee) }}" min="0">
                                @error('length_of_visa.'.$evisaLength::OneMonthMultiple.'.admin_and_government_fee')
                                    <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="input-area">
                                <label for="length_of_visa_3_month_single_price" class="form-label">3 tháng - Single Entry - Giá ($)</label>
                                <input type="number" step="0.01" id="length_of_visa_3_month_single_price" name="length_of_visa[{{ $evisaLength::ThreeMonthSingle }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('length_of_visa.'.$evisaLength::ThreeMonthSingle.'.price', $lengthOfVisa?->threeMonthSingle->price) }}" required>
                                @error('length_of_visa.'.$evisaLength::ThreeMonthSingle.'.price')
                                    <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="input-area">
                                <label for="length_of_visa_3_month_single_fee" class="form-label">3 tháng - Single Entry - Phí admin và chính phủ ($)</label>
                                <input type="number" step="0.01" id="length_of_visa_3_month_single_fee" name="length_of_visa[{{ $evisaLength::ThreeMonthSingle }}][admin_and_government_fee]" class="form-control" placeholder="Nhập phí admin và chính phủ ($)" value="{{ old('length_of_visa.'.$evisaLength::ThreeMonthSingle.'.admin_and_government_fee', $lengthOfVisa?->threeMonthSingle->adminAndGovernmentFee) }}" min="0">
                                @error('length_of_visa.'.$evisaLength::ThreeMonthSingle.'.admin_and_government_fee')
                                    <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="input-area">
                                <label for="length_of_visa_3_month_multiple_price" class="form-label">3 tháng - Multiple Entry - Giá ($)</label>
                                <input type="number" step="0.01" id="length_of_visa_3_month_multiple_price" name="length_of_visa[{{ $evisaLength::ThreeMonthMultiple }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('length_of_visa.'.$evisaLength::ThreeMonthMultiple.'.price', $lengthOfVisa?->threeMonthMultiple->price) }}" required>
                                @error('length_of_visa.'.$evisaLength::ThreeMonthMultiple.'.price')
                                    <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                                @enderror
                            </div>
                            <div class="input-area">
                                <label for="length_of_visa_3_month_multiple_fee" class="form-label">3 tháng - Multiple Entry - Phí admin và chính phủ ($)</label>
                                <input type="number" step="0.01" id="length_of_visa_3_month_multiple_fee" name="length_of_visa[{{ $evisaLength::ThreeMonthMultiple }}][admin_and_government_fee]" class="form-control" placeholder="Nhập phí admin và chính phủ ($)" value="{{ old('length_of_visa.'.$evisaLength::ThreeMonthMultiple.'.admin_and_government_fee', $lengthOfVisa?->threeMonthMultiple->adminAndGovernmentFee) }}" min="0">
                                @error('length_of_visa.'.$evisaLength::ThreeMonthMultiple.'.admin_and_government_fee')
                                    <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-100 dark:border-slate-700 pt-4">
                    <h5 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Thời gian xử lý</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <div class="input-area">
                            <label for="processing_time_normal" class="form-label">Normal ($)</label>
                            <input type="number" step="0.01" id="processing_time_normal" name="processing_time[{{ $evisaProcessingTime::Normal }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('processing_time.'.$evisaProcessingTime::Normal.'.price', $processingTime?->normal->price) }}" required>
                            @error('processing_time.'.$evisaProcessingTime::Normal.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-area">
                            <label for="processing_time_urgent" class="form-label">Urgent ($)</label>
                            <input type="number" step="0.01" id="processing_time_urgent" name="processing_time[{{ $evisaProcessingTime::Urgent }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('processing_time.'.$evisaProcessingTime::Urgent.'.price', $processingTime?->urgent->price) }}" required>
                            @error('processing_time.'.$evisaProcessingTime::Urgent.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-area">
                            <label for="processing_time_super_urgent" class="form-label">Super Urgent ($)</label>
                            <input type="number" step="0.01" id="processing_time_super_urgent" name="processing_time[{{ $evisaProcessingTime::SuperUrgent }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('processing_time.'.$evisaProcessingTime::SuperUrgent.'.price', $processingTime?->superUrgent->price) }}" required>
                            @error('processing_time.'.$evisaProcessingTime::SuperUrgent.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-area">
                            <label for="processing_time_express" class="form-label">Express ($)</label>
                            <input type="number" step="0.01" id="processing_time_express" name="processing_time[{{ $evisaProcessingTime::Express }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('processing_time.'.$evisaProcessingTime::Express.'.price', $processingTime?->express->price) }}" required>
                            @error('processing_time.'.$evisaProcessingTime::Express.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-area">
                            <label for="processing_time_emergency" class="form-label">Emergency ($)</label>
                            <input type="number" step="0.01" id="processing_time_emergency" name="processing_time[{{ $evisaProcessingTime::Emergency }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('processing_time.'.$evisaProcessingTime::Emergency.'.price', $processingTime?->emergency->price) }}" required>
                            @error('processing_time.'.$evisaProcessingTime::Emergency.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-area">
                            <label for="processing_time_weekend_or_holiday" class="form-label">Weekend/Holiday ($)</label>
                            <input type="number" step="0.01" id="processing_time_weekend_or_holiday" name="processing_time[{{ $evisaProcessingTime::WeekendOrHoliday }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('processing_time.'.$evisaProcessingTime::WeekendOrHoliday.'.price', $processingTime?->weekendOrHoliday->price) }}" required>
                            @error('processing_time.'.$evisaProcessingTime::WeekendOrHoliday.'.price')
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