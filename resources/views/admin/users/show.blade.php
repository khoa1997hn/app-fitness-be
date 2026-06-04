@extends('admin.layouts.app')

@section('title', 'Chi tiết khách hàng')

@php
    $subscriptionStatusColors = [
        'trial'        => 'bg-info-500 text-info-500',
        'active'       => 'bg-success-500 text-success-500',
        'expired'      => 'bg-danger-500 text-danger-500',
        'cancelled'    => 'bg-slate-500 text-slate-500',
        'grace_period' => 'bg-warning-500 text-warning-500',
        'refunded'     => 'bg-danger-500 text-danger-500',
        'on_hold'      => 'bg-warning-500 text-warning-500',
        'pending'      => 'bg-info-500 text-info-500',
    ];
    $subscriptionStatusLabels = [
        'trial'        => 'Dùng thử',
        'active'       => 'Còn hiệu lực',
        'expired'      => 'Hết hạn',
        'cancelled'    => 'Đã hủy',
        'grace_period' => 'Gia hạn',
        'refunded'     => 'Hoàn tiền',
        'on_hold'      => 'Tạm giữ',
        'pending'      => 'Đang chờ',
    ];
    $planColors = [
        'basic' => 'bg-primary-500 text-primary-500',
        'plus'  => 'bg-info-500 text-info-500',
        'all'   => 'bg-success-500 text-success-500',
    ];
    $planLabels = [
        'basic' => 'Basic',
        'plus'  => 'Plus',
        'all'   => 'All Access',
    ];
@endphp

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
            <a href="{{ route('admin.users.index') }}">Danh sách khách hàng</a>
            <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
        </li>
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">
            Chi tiết khách hàng #{{ $user->id }}
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->

<div class="space-y-5">

    {{-- Card: Thông tin khách hàng --}}
    <div class="card">
        <header class="card-header noborder">
            <h4 class="card-title">Thông tin khách hàng</h4>
            <a href="{{ route('admin.users.index') }}" class="btn btn-light inline-flex items-center gap-1 px-4">
                <iconify-icon icon="heroicons-outline:arrow-left"></iconify-icon>
                Quay lại
            </a>
        </header>
        <div class="card-body px-6 pb-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div class="flex flex-col gap-1">
                    <span class="text-slate-400">ID</span>
                    <span class="font-medium text-slate-700 dark:text-white">{{ $user->id }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-slate-400">Họ tên</span>
                    <span class="font-medium text-slate-700 dark:text-white">{{ $user->first_name }} {{ $user->last_name }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-slate-400">Email</span>
                    <span class="font-medium text-slate-700 dark:text-white">{{ $user->email }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-slate-400">Số điện thoại</span>
                    <span class="font-medium text-slate-700 dark:text-white">{{ $user->phone ?? 'N/A' }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-slate-400">Ngày sinh</span>
                    <span class="font-medium text-slate-700 dark:text-white">{{ $user->dob?->format('d/m/Y') ?? 'N/A' }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-slate-400">Ngày đăng ký</span>
                    <span class="font-medium text-slate-700 dark:text-white">{{ $user->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-slate-400">Gói hiện tại</span>
                    @if ($user->plan)
                        <span class="badge {{ $planColors[$user->plan->value] ?? 'bg-slate-500 text-slate-500' }} bg-opacity-30 rounded-3xl w-fit">
                            {{ $planLabels[$user->plan->value] ?? $user->plan->value }}
                        </span>
                    @else
                        <span class="text-slate-400">Chưa có gói</span>
                    @endif
                </div>
                <div class="flex flex-col gap-1">
                    <span class="text-slate-400">Trạng thái subscription</span>
                    @if ($user->subscription_status)
                        <span class="badge {{ $subscriptionStatusColors[$user->subscription_status->value] ?? 'bg-slate-500 text-slate-500' }} bg-opacity-30 rounded-3xl w-fit">
                            {{ $subscriptionStatusLabels[$user->subscription_status->value] ?? $user->subscription_status->value }}
                        </span>
                    @else
                        <span class="text-slate-400">N/A</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Card: Subscription hiện tại --}}
    <div class="card">
        <header class="card-header noborder">
            <h4 class="card-title">Subscription hiện tại</h4>
            <a href="{{ route('admin.users.subscription.edit', $user) }}" class="btn btn-dark inline-flex items-center gap-1 px-4">
                <iconify-icon icon="heroicons-outline:pencil-square"></iconify-icon>
                {{ $user->subscription ? 'Sửa subscription' : 'Tạo subscription' }}
            </a>
        </header>
        <div class="card-body px-6 pb-6">
            @if ($user->subscription)
                @php $sub = $user->subscription; @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <div class="flex flex-col gap-1">
                        <span class="text-slate-400">Gói</span>
                        <span class="badge {{ $planColors[$sub->plan->value] ?? 'bg-slate-500 text-slate-500' }} bg-opacity-30 rounded-3xl w-fit">
                            {{ $planLabels[$sub->plan->value] ?? $sub->plan->value }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-slate-400">Trạng thái</span>
                        <span class="badge {{ $subscriptionStatusColors[$sub->status->value] ?? 'bg-slate-500 text-slate-500' }} bg-opacity-30 rounded-3xl w-fit">
                            {{ $subscriptionStatusLabels[$sub->status->value] ?? $sub->status->value }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-slate-400">Nhà cung cấp</span>
                        <span class="font-medium text-slate-700 dark:text-white flex items-center gap-1">
                            @if ($sub->provider->value === 'google_iap')
                                <iconify-icon icon="logos:google-play-icon" class="text-base"></iconify-icon> Google IAP
                            @elseif ($sub->provider->value === 'apple_iap')
                                <iconify-icon icon="logos:apple" class="text-base"></iconify-icon> Apple IAP
                            @else
                                {{ $sub->provider->description }}
                            @endif
                        </span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-slate-400">Số tiền</span>
                        <span class="font-medium text-slate-700 dark:text-white">
                            {{ number_format($sub->amount, 2) }} {{ $sub->currency }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-slate-400">Chu kỳ thanh toán</span>
                        <span class="font-medium text-slate-700 dark:text-white">{{ $sub->billing_cycle->value }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-slate-400">Tự gia hạn</span>
                        <span class="font-medium text-slate-700 dark:text-white">
                            @if ($sub->auto_renew)
                                <span class="badge bg-success-500 text-success-500 bg-opacity-30 rounded-3xl">Bật</span>
                            @else
                                <span class="badge bg-slate-500 text-slate-500 bg-opacity-30 rounded-3xl">Tắt</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-slate-400">Ngày mua</span>
                        <span class="font-medium text-slate-700 dark:text-white">{{ $sub->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-slate-400">Ngày hết hạn</span>
                        <span class="font-medium text-slate-700 dark:text-white">{{ $sub->expires_at?->format('d/m/Y H:i') ?? 'N/A' }}</span>
                    </div>
                    @if ($sub->trial_ends_at)
                        <div class="flex flex-col gap-1">
                            <span class="text-slate-400">Hết hạn dùng thử</span>
                            <span class="font-medium text-slate-700 dark:text-white">{{ $sub->trial_ends_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                    @if ($sub->cancelled_at)
                        <div class="flex flex-col gap-1">
                            <span class="text-slate-400">Ngày hủy</span>
                            <span class="font-medium text-danger-500">{{ $sub->cancelled_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                    @if ($sub->grace_period_ends_at)
                        <div class="flex flex-col gap-1">
                            <span class="text-slate-400">Hết gia hạn</span>
                            <span class="font-medium text-warning-500">{{ $sub->grace_period_ends_at->format('d/m/Y H:i') }}</span>
                        </div>
                    @endif
                </div>
            @else
                <p class="text-slate-400 text-sm">Người dùng chưa có subscription.</p>
            @endif
        </div>
    </div>

    {{-- Card: Lịch sử giao dịch --}}
    <div class="card">
        <header class="card-header noborder">
            <h4 class="card-title">Lịch sử giao dịch</h4>
        </header>
        <div class="card-body px-6 pb-6">
            @if ($transactions->isNotEmpty())
                <div class="overflow-x-auto -mx-6">
                    <div class="inline-block min-w-full align-middle">
                        <div class="overflow-hidden">
                            <table class="min-w-full divide-y divide-slate-100 table-fixed dark:divide-slate-700">
                                <thead class="border-t border-slate-100 dark:border-slate-800">
                                    <tr>
                                        <th scope="col" class="table-th">Nhà cung cấp</th>
                                        <th scope="col" class="table-th">Transaction ID</th>
                                        <th scope="col" class="table-th">Product ID</th>
                                        <th scope="col" class="table-th">Ngày giao dịch</th>
                                        <th scope="col" class="table-th">Ngày hết hạn</th>
                                        <th scope="col" class="table-th">Trạng thái</th>
                                        <th scope="col" class="table-th">Ngày tạo</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-slate-100 dark:bg-slate-800 dark:divide-slate-700">
                                    @foreach ($transactions as $tx)
                                        <tr>
                                            <td class="table-td">
                                                <span class="inline-flex items-center gap-1">
                                                    @if ($tx['provider'] === 'Google IAP')
                                                        <iconify-icon icon="logos:google-play-icon" class="text-base"></iconify-icon>
                                                    @else
                                                        <iconify-icon icon="logos:apple" class="text-base"></iconify-icon>
                                                    @endif
                                                    {{ $tx['provider'] }}
                                                </span>
                                            </td>
                                            <td class="table-td text-xs text-slate-500 max-w-[160px] truncate" title="{{ $tx['transaction_id'] }}">
                                                {{ $tx['transaction_id'] ?? 'N/A' }}
                                            </td>
                                            <td class="table-td text-xs">{{ $tx['product_id'] ?? 'N/A' }}</td>
                                            <td class="table-td">{{ $tx['purchase_date']?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                                            <td class="table-td">{{ $tx['expires_date']?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                                            <td class="table-td">
                                                @if ($tx['status'])
                                                    <span class="badge {{ $subscriptionStatusColors[$tx['status']] ?? 'bg-slate-500 text-slate-500' }} bg-opacity-30 rounded-3xl text-xs">
                                                        {{ $subscriptionStatusLabels[$tx['status']] ?? $tx['status'] }}
                                                    </span>
                                                @else
                                                    <span class="text-slate-400">N/A</span>
                                                @endif
                                            </td>
                                            <td class="table-td">{{ $tx['created_at']?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <p class="text-slate-400 text-sm">Chưa có giao dịch nào.</p>
            @endif
        </div>
    </div>

</div>
@endsection
