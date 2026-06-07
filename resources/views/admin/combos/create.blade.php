@extends('admin.layouts.app')

@section('title', 'Thêm combo')

@section('content')
<div class="mb-5">
    <ul class="m-0 p-0 list-none">
        <li class="inline-block relative top-[3px] text-base text-primary-500 font-Inter">
            <a href="{{ route('admin.dashboard') }}">
                <iconify-icon icon="heroicons-outline:home"></iconify-icon>
                <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
            </a>
        </li>
        <li class="inline-block relative top-[3px] text-base text-primary-500 font-Inter">
            <a href="{{ route('admin.combos.index') }}">Combo</a>
            <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
        </li>
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">Thêm combo</li>
    </ul>
</div>

<div class="space-y-5">
    <div class="card">
        <header class="card-header noborder">
            <h4 class="card-title">Thêm combo mới</h4>
        </header>
        <div class="card-body px-6 pb-6">
            <form action="{{ route('admin.combos.store') }}" method="POST" id="combo-form">
                @csrf
                @include('admin.combos._form', ['combo' => null, 'selectedProgramIds' => old('program_ids', []), 'infos' => old('infos', [])])
                <div class="flex gap-2 mt-5">
                    <a href="{{ route('admin.combos.index') }}" class="btn btn-light">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Tạo combo</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('admin.combos._scripts')
@endpush
