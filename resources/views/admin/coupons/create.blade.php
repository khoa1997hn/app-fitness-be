@extends('admin.layouts.app')

@section('title', 'Thêm coupon')

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
            <a href="{{ route('admin.coupons.index') }}">
                Danh sách coupon
                <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
            </a>
        </li>
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">
            Thêm coupon
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->
<div class="space-y-5">
    <div class="card">
        <div class="card-body flex flex-col p-6">
            <header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5 -mx-6 px-6">
                <div class="flex-1">
                    <div class="card-title text-slate-900 dark:text-white">Thêm coupon</div>
                </div>
            </header>
            <form action="{{ route('admin.coupons.store') }}" method="POST" class="card-text h-full space-y-4 max-w-4xl">
                @csrf

                <div class="input-area">
                    <label for="code" class="form-label">Mã coupon <span class="text-danger-500">*</span></label>
                    <input type="text" id="code" name="code" class="form-control" placeholder="Nhập mã coupon" value="{{ old('code') }}" required>
                    @error('code')
                        <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="input-area">
                        <label for="start_at" class="form-label">Ngày bắt đầu</label>
                        <input type="datetime-local" id="start_at" name="start_at" class="form-control" value="{{ old('start_at') }}">
                        @error('start_at')
                            <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="input-area">
                        <label for="end_at" class="form-label">Ngày kết thúc</label>
                        <input type="datetime-local" id="end_at" name="end_at" class="form-control" value="{{ old('end_at') }}">
                        @error('end_at')
                            <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                        @enderror
                    </div>
                </div>

                <div class="input-area">
                    <label for="total_quantity" class="form-label">Tổng số lượng <span class="text-danger-500">*</span></label>
                    <input type="number" id="total_quantity" name="total_quantity" class="form-control" placeholder="Nhập tổng số lượng" value="{{ old('total_quantity', 0) }}" min="0" required>
                    @error('total_quantity')
                        <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-area">
                    <label for="discount_type" class="form-label">Loại giảm giá <span class="text-danger-500">*</span></label>
                    <select id="discount_type" name="discount_type" class="form-control" required>
                        <option value="">Chọn loại giảm giá</option>
                        <option value="{{ $discountTypes::Percentage }}" {{ old('discount_type') == $discountTypes::Percentage ? 'selected' : '' }}>Phần trăm (%)</option>
                        <option value="{{ $discountTypes::FixedAmount }}" {{ old('discount_type') == $discountTypes::FixedAmount ? 'selected' : '' }}>Số tiền ($)</option>
                    </select>
                    @error('discount_type')
                        <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-area">
                    <label for="discount_value" class="form-label">Giá trị giảm giá <span class="text-danger-500">*</span></label>
                    <input type="number" step="0.01" id="discount_value" name="discount_value" class="form-control" placeholder="Nhập giá trị giảm giá" value="{{ old('discount_value') }}" min="0" required>
                    <small class="text-slate-500">Nếu là phần trăm: nhập từ 0-100. Nếu là số tiền: nhập số tiền giảm.</small>
                    @error('discount_value')
                        <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <a href="{{ route('admin.coupons.index') }}" class="btn inline-flex justify-center btn-light px-6">Hủy</a>
                    <button type="submit" class="btn inline-flex justify-center btn-primary px-6">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

