@extends('admin.layouts.app')

@section('title', 'Sửa combo')

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
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">Sửa combo</li>
    </ul>
</div>

<div class="space-y-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <header class="card-header noborder">
            <h4 class="card-title">Sửa combo #{{ $combo->id }}</h4>
        </header>
        <div class="card-body px-6 pb-6">
            <form action="{{ route('admin.combos.update', $combo) }}" method="POST" id="combo-form">
                @csrf
                @method('PUT')
                @php
                    $selectedProgramIds = old('program_ids', $combo->programs->pluck('id')->all());
                    $infos = old('infos', $combo->infos->values()->map(fn ($info, $index) => [
                        'icon' => [
                            'path' => $info->icon->path ?? '',
                            'name' => $info->icon->name ?? '',
                            'extension' => $info->icon->extension ?? '',
                            'size' => $info->icon->size ?? '',
                        ],
                        'translations' => collect(config('translatable.locales'))->mapWithKeys(fn ($locale) => [
                            $locale => ['text' => $info->translate($locale)?->text ?? ''],
                        ])->all(),
                    ])->all());
                @endphp
                @include('admin.combos._form', ['combo' => $combo, 'selectedProgramIds' => $selectedProgramIds, 'infos' => $infos])
                <div class="flex gap-2 mt-5">
                    <a href="{{ route('admin.combos.index') }}" class="btn btn-light">Quay lại</a>
                    <button type="submit" class="btn btn-primary">Lưu thay đổi</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@include('admin.combos._scripts')
@endpush
