@extends('admin.layouts.app')

@section('title', 'Thêm tour')

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
            <a href="{{ route('admin.tours.index') }}">
                Danh sách tour
                <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
            </a>
        </li>
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">
            Thêm tour
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->
<div class="space-y-5">
    <div class="card">
        <div class="card-body flex flex-col p-6">
            <header class="flex mb-5 items-center border-b border-slate-100 dark:border-slate-700 pb-5 -mx-6 px-6">
                <div class="flex-1">
                    <div class="card-title text-slate-900 dark:text-white">Thêm tour</div>
                </div>
            </header>
            <form action="{{ route('admin.tours.store') }}" method="POST" class="card-text h-full space-y-4 max-w-4xl">
                @csrf

                <div class="input-area">
                    <label for="name" class="form-label">Tên tour <span class="text-danger-500">*</span></label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="Nhập tên tour" value="{{ old('name') }}" required>
                    @error('name')
                        <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                    @enderror
                </div>

                <div class="input-area">
                    <label for="link" class="form-label">Link <span class="text-danger-500">*</span></label>
                    <input type="url" id="link" name="link" class="form-control" placeholder="Nhập link tour" value="{{ old('link') }}" required>
                    @error('link')
                        <span class="font-Inter text-sm text-danger-500 pt-2">{{ $message }}</span>
                    @enderror
                </div>

                <div class="flex justify-end gap-2 mt-4">
                    <a href="{{ route('admin.tours.index') }}" class="btn inline-flex justify-center btn-light px-6">Hủy</a>
                    <button type="submit" class="btn inline-flex justify-center btn-primary px-6">Lưu</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

