@extends('admin.layouts.app')

@section('title', 'Danh sách bộ môn')

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
            Danh sách bộ môn
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->
<div class="space-y-5">
    <div class="card">
        <header class="card-header noborder">
            <h4 class="card-title">Danh sách bộ môn</h4>
        </header>
        <div class="card-body px-6 pb-6">
            <div class="overflow-x-auto -mx-6">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700">
                            <thead class="border-t border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th scope="col" class="table-th">ID</th>
                                    <th scope="col" class="table-th">Ảnh cover</th>
                                    <th scope="col" class="table-th">Tên</th>
                                    <th scope="col" class="table-th">Đánh giá</th>
                                    <th scope="col" class="table-th">Số yêu thích</th>
                                    <th scope="col" class="table-th">Số bài học</th>
                                    <th scope="col" class="table-th">Tổng thời lượng</th>
                                    <th scope="col" class="table-th">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                @forelse ($programs as $program)
                                    @php $totalSeconds = $program->totalDurationSeconds(); @endphp
                                    <tr>
                                        <td class="table-td">{{ $program->id }}</td>
                                        <td class="table-td">
                                            @if ($program->cover)
                                                <img src="{{ $program->cover->url() }}" alt="cover" class="w-16 h-16 object-cover rounded">
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="table-td">{{ $program->name }}</td>
                                        <td class="table-td">{{ $program->rating ?? 'N/A' }}</td>
                                        <td class="table-td">{{ $program->favorites_count }}</td>
                                        <td class="table-td">{{ $program->lessons_count }}</td>
                                        <td class="table-td">{{ floor($totalSeconds / 60) }} phút {{ $totalSeconds % 60 }} giây</td>
                                        <td class="table-td">
                                            <div class="relative">
                                                <div class="dropdown relative">
                                                    <button class="text-xl text-center block w-full" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <iconify-icon icon="heroicons-outline:dots-vertical"></iconify-icon>
                                                    </button>
                                                    <ul class="dropdown-menu min-w-[120px] absolute text-sm text-slate-700 dark:text-white hidden bg-white dark:bg-slate-700 shadow z-[2] float-left overflow-hidden list-none text-left rounded-lg mt-1 m-0 bg-clip-padding border-none">
                                                        <li>
                                                            <a href="{{ route('admin.programs.edit', $program) }}" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600 dark:hover:text-white w-full text-left">
                                                                Sửa
                                                            </a>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('admin.programs.destroy', $program) }}" method="POST" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600 dark:hover:text-white w-full text-left" onclick="return confirm('Bạn có chắc chắn muốn xóa bộ môn này? Toàn bộ bài học và video liên quan sẽ bị xóa.')">
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
                                        <td colspan="8" class="table-td text-center">Không có dữ liệu</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
