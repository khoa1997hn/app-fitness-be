@extends('admin.layouts.app')

@section('title', 'Danh sách combo')

@section('content')
<div class="mb-5">
    <ul class="m-0 p-0 list-none">
        <li class="inline-block relative top-[3px] text-base text-primary-500 font-Inter">
            <a href="{{ route('admin.dashboard') }}">
                <iconify-icon icon="heroicons-outline:home"></iconify-icon>
                <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
            </a>
        </li>
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">
            Danh sách combo
        </li>
    </ul>
</div>

<div class="space-y-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <header class="card-header noborder">
            <div class="flex items-center justify-between gap-4">
                <h4 class="card-title">Danh sách combo</h4>
                <a href="{{ route('admin.combos.create') }}" class="btn btn-primary px-6">
                    <iconify-icon icon="heroicons-outline:plus" class="mr-2"></iconify-icon>
                    Thêm combo
                </a>
            </div>
        </header>
        <div class="card-body px-6 pb-6">
            <div class="overflow-x-auto -mx-6">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700">
                            <thead class="border-t border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th scope="col" class="table-th">ID</th>
                                    <th scope="col" class="table-th">Ảnh</th>
                                    <th scope="col" class="table-th">Tên (vi)</th>
                                    <th scope="col" class="table-th">Số bộ môn</th>
                                    <th scope="col" class="table-th">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                @forelse ($combos as $combo)
                                    @php $viTranslation = $combo->translate('vi'); @endphp
                                    <tr>
                                        <td class="table-td">{{ $combo->id }}</td>
                                        <td class="table-td">
                                            @if ($viTranslation?->cover)
                                                <img src="{{ $viTranslation->cover->url() }}" alt="cover" class="w-20 h-12 object-cover rounded">
                                            @else
                                                <span class="text-slate-400">N/A</span>
                                            @endif
                                        </td>
                                        <td class="table-td">
                                            <a href="{{ route('admin.combos.edit', $combo) }}" class="text-primary-500 hover:underline font-medium">
                                                {{ $viTranslation?->name ?? '—' }}
                                            </a>
                                        </td>
                                        <td class="table-td">{{ $combo->programs_count }}</td>
                                        <td class="table-td">
                                            <div class="flex gap-2">
                                                <a href="{{ route('admin.combos.edit', $combo) }}" class="btn btn-sm btn-outline-primary">Sửa</a>
                                                <form action="{{ route('admin.combos.destroy', $combo) }}" method="POST" onsubmit="return confirm('Bạn có chắc muốn xóa combo này?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Xóa</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="table-td text-center text-slate-500">Chưa có combo nào.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @if ($combos->hasPages())
                <div class="mt-4">{{ $combos->links() }}</div>
            @endif
        </div>
    </div>
</div>
@endsection
