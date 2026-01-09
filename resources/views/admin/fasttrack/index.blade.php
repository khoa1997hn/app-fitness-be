@extends('admin.layouts.app')

@section('title', 'Cài đặt Fast Track')

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
            Cài đặt Fast Track
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->
<div class="space-y-5">
    <div class="card">
        <div class="card-body flex flex-col p-6">
            <header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5 -mx-6 px-6">
                <div class="flex-1">
                    <div class="card-title text-slate-900 dark:text-white">Cài đặt Fast Track</div>
                </div>
            </header>
            <form action="{{ route('admin.fasttrack.store') }}" method="POST" class="card-text h-full space-y-4 max-w-4xl">
                @csrf

                <div class="border-t border-slate-100 dark:border-slate-700 pt-4">
                    <h5 class="text-lg font-semibold text-slate-900 dark:text-white mb-4">Loại dịch vụ</h5>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-4">
                        <div class="input-area">
                            <label for="type_normal" class="form-label">Normal ($)</label>
                            <input type="number" step="0.01" id="type_normal" name="type[{{ $fastTrackType::Normal }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('type.'.$fastTrackType::Normal.'.price', $type?->normal->price) }}" required>
                            @error('type.'.$fastTrackType::Normal.'.price')
                                <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="input-area">
                            <label for="type_vip_b" class="form-label">VIP B ($)</label>
                            <input type="number" step="0.01" id="type_vip_b" name="type[{{ $fastTrackType::VipB }}][price]" class="form-control" placeholder="Nhập giá ($)" value="{{ old('type.'.$fastTrackType::VipB.'.price', $type?->vipB->price) }}" required>
                            @error('type.'.$fastTrackType::VipB.'.price')
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
