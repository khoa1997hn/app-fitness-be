@extends('admin.layouts.app')

@section('title', 'Danh sách banner')

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
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">
            Danh sách banner
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->

<div class="space-y-5">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card">
        <header class="card-header noborder">
            <div class="flex items-center justify-between gap-4">
                <h4 class="card-title">Danh sách banner</h4>
                <a href="{{ route('admin.banners.create') }}" class="btn btn-primary px-6">
                    <iconify-icon icon="heroicons-outline:plus" class="mr-2"></iconify-icon>
                    Thêm banner
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
                                    <th scope="col" class="table-th">Mô tả</th>
                                    <th scope="col" class="table-th">Link URL (vi)</th>
                                    <th scope="col" class="table-th">Thứ tự (vi)</th>
                                    <th scope="col" class="table-th">Kích hoạt (vi)</th>
                                    <th scope="col" class="table-th">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                @forelse ($banners as $banner)
                                    @php $viTranslation = $banner->translate('vi'); @endphp
                                    <tr>
                                        <td class="table-td">{{ $banner->id }}</td>
                                        <td class="table-td">
                                            @if ($viTranslation?->image)
                                                <img src="{{ $viTranslation->image->url() }}" alt="banner" class="w-20 h-12 object-cover rounded">
                                            @else
                                                <span class="text-slate-400">N/A</span>
                                            @endif
                                        </td>
                                        <td class="table-td">{{ $banner->description ?? '—' }}</td>
                                        <td class="table-td">
                                            @if ($viTranslation?->link_url)
                                                <span class="text-xs truncate max-w-[200px] block" title="{{ $viTranslation->link_url }}">{{ $viTranslation->link_url }}</span>
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="table-td">{{ $viTranslation?->order ?? 0 }}</td>
                                        <td class="table-td">
                                            @if ($viTranslation?->is_active)
                                                <span class="badge bg-success-500 text-success-500 bg-opacity-30 rounded-3xl">Hoạt động</span>
                                            @else
                                                <span class="badge bg-danger-500 text-danger-500 bg-opacity-30 rounded-3xl">Tắt</span>
                                            @endif
                                        </td>
                                        <td class="table-td">
                                            <div class="relative">
                                                <div class="dropdown relative">
                                                    <button class="text-xl text-center block w-full" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <iconify-icon icon="heroicons-outline:dots-vertical"></iconify-icon>
                                                    </button>
                                                    <ul class="dropdown-menu min-w-[120px] absolute text-sm text-slate-700 dark:text-white hidden bg-white dark:bg-slate-700 shadow z-[2] float-left overflow-hidden list-none text-left rounded-lg mt-1 m-0 bg-clip-padding border-none">
                                                        <li>
                                                            <a href="{{ route('admin.banners.edit', $banner) }}" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600 dark:hover:text-white w-full text-left">
                                                                Sửa
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('admin.banners.destroy', $banner) }}" method="POST" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600 dark:hover:text-white w-full text-left" onclick="return confirm('Bạn có chắc chắn muốn xóa banner này?')">
                                                                    Xóa
                                                                </button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="table-td text-center">Không có dữ liệu</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            @if ($banners->hasPages())
                <div class="mt-6 flex justify-end">
                    {{ $banners->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
