@extends('admin.layouts.app')

@section('title', 'Thêm bài học')

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
            Thêm bài học
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->

<div class="card">
    <header class="card-header noborder">
        <h4 class="card-title">Thêm bài học</h4>
    </header>
    <div class="card-body px-6 pb-6">
        <form action="{{ route('admin.programs.lessons.store', $program) }}" method="POST">
            @csrf
            @include('admin.lessons._form')

            <div class="flex gap-2">
                <a href="{{ route('admin.programs.edit', $program) }}" class="btn btn-light">Quay lại</a>
                <button type="submit" class="btn btn-primary">Tạo bài học</button>
            </div>
        </form>
    </div>
</div>
@endsection
