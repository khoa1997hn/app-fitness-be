@extends('admin.layouts.app')

@section('title', 'Sửa bài học')

@section('content')
<!-- BEGIN: Breadcrumb -->
<div class="mb-5">
    <ul class="m-0 p-0 list-none">
        <li class="inline-block relative top-[3px] text-base text-primary-500 font-Inter">
            <a href="{{ route('admin.dashboard') }}">
                <iconify-icon icon="heroicons-outline:home"></iconify-icon>
                <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
            </a>
        </li>
        <li class="inline-block relative top-[3px] text-base text-primary-500 font-Inter">
            <a href="{{ route('admin.programs.edit', $program) }}">Bộ môn #{{ $program->id }}</a>
            <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
        </li>
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">
            Sửa bài học #{{ $lesson->id }}
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->

<div class="card">
    <header class="card-header noborder">
        <div class="flex items-center justify-between gap-4">
            <h4 class="card-title">Sửa bài học</h4>
            <span class="text-sm text-slate-500">Số yêu thích: <strong>{{ $lesson->favorites_count }}</strong></span>
        </div>
    </header>
    <div class="card-body px-6 pb-6">
        <form action="{{ route('admin.programs.lessons.update', [$program, $lesson]) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.lessons._form')

            <div class="flex gap-2">
                <a href="{{ route('admin.programs.edit', $program) }}" class="btn btn-light">Quay lại</a>
                <button type="submit" class="btn btn-primary">Lưu</button>
            </div>
        </form>
    </div>
</div>
@endsection
