@extends('admin.layouts.app')

@section('title', 'Danh sách khách hàng')

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
            Danh sách khách hàng
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->
<div class="space-y-5">
    <div class="card">
        <header class="card-header noborder">
            <div class="flex items-center justify-between gap-4">
                <h4 class="card-title">Danh sách khách hàng</h4>
                <form action="{{ route('admin.users.export') }}" method="GET" class="inline">
                    @foreach(request()->except('page') as $key => $value)
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endforeach
                    <button type="submit" class="btn inline-flex justify-center btn-primary px-6 whitespace-nowrap">
                        <iconify-icon icon="heroicons-outline:download" class="mr-2"></iconify-icon>
                        Export CSV
                    </button>
                </form>
            </div>
        </header>
        <div class="card-body px-6 pb-6">
            <form action="{{ route('admin.users.index') }}" method="GET" class="mb-6">
                <div class="flex flex-wrap gap-4 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <div class="input-area">
                            <label for="q" class="form-label">Tìm kiếm</label>
                            <input type="text" name="q" id="q" class="form-control" placeholder="Tên, Email, SĐT..." value="{{ request('q') }}">
                        </div>
                    </div>
                    <div class="w-[160px]">
                        <div class="input-area">
                            <label for="start_date" class="form-label">Từ ngày</label>
                            <input type="date" name="start_date" id="start_date" class="form-control" value="{{ request('start_date') }}">
                        </div>
                    </div>
                    <div class="w-[160px]">
                        <div class="input-area">
                            <label for="end_date" class="form-label">Đến ngày</label>
                            <input type="date" name="end_date" id="end_date" class="form-control" value="{{ request('end_date') }}">
                        </div>
                    </div>
                    <div class="w-auto flex gap-2">
                        <a href="{{ route('admin.users.index') }}" class="btn inline-flex justify-center btn-light px-6">Reset</a>
                        <button type="submit" class="btn inline-flex justify-center btn-primary px-6">Lọc</button>
                    </div>
                </div>
            </form>

            <div class="overflow-x-auto -mx-6">
                <div class="inline-block min-w-full align-middle">
                    <div class="overflow-hidden">
                        <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700">
                            <thead class="border-t border-slate-100 dark:border-slate-800">
                                <tr>
                                    <th scope="col" class="table-th">ID</th>
                                    <th scope="col" class="table-th">Tên</th>
                                    <th scope="col" class="table-th">Email</th>
                                    <th scope="col" class="table-th">SĐT</th>
                                    <th scope="col" class="table-th">Email giới thiệu</th>
                                    <th scope="col" class="table-th">Ngày tạo</th>
                                    <th scope="col" class="table-th">Thao tác</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                @forelse ($users as $user)
                                    <tr>
                                        <td class="table-td">{{ $user->id }}</td>
                                        <td class="table-td">{{ $user->name }}</td>
                                        <td class="table-td">{{ $user->email }}</td>
                                        <td class="table-td">{{ $user->phone ?? 'N/A' }}</td>
                                        <td class="table-td">{{ $user->referral_email ?? 'N/A' }}</td>
                                        <td class="table-td">{{ $user->created_at->format('d/m/Y H:i') }}</td>
                                        <td class="table-td">
                                            <div class="relative">
                                                <div class="dropdown relative">
                                                    <button class="text-xl text-center block w-full" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <iconify-icon icon="heroicons-outline:dots-vertical"></iconify-icon>
                                                    </button>
                                                    <ul class="dropdown-menu min-w-[120px] absolute text-sm text-slate-700 dark:text-white hidden bg-white dark:bg-slate-700 shadow z-[2] float-left overflow-hidden list-none text-left rounded-lg mt-1 m-0 bg-clip-padding border-none">
                                                        <li>
                                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit" class="text-slate-600 dark:text-white block font-Inter font-normal px-4 py-2 hover:bg-slate-100 dark:hover:bg-slate-600 dark:hover:text-white w-full text-left" onclick="return confirm('Bạn có chắc chắn muốn xóa khách hàng này?')">
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

            @if($users->hasPages())
                <div class="mt-6 flex flex-col md:flex-row md:items-center gap-4">
                    <div class="text-sm text-slate-600 dark:text-slate-400 flex-1">
                        Hiển thị <span class="font-medium text-slate-900 dark:text-white">{{ $users->firstItem() }}</span> - <span class="font-medium text-slate-900 dark:text-white">{{ $users->lastItem() }}</span> trong tổng số <span class="font-medium text-slate-900 dark:text-white">{{ $users->total() }}</span> bản ghi
                    </div>
                    <div class="flex justify-end">
                        {{ $users->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
