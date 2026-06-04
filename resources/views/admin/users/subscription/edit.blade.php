@extends('admin.layouts.app')

@section('title', $user->subscription ? 'Sửa subscription' : 'Tạo subscription')

@php
    use App\Share\Enums\Plan;
    $sub = $user->subscription;
    $defaultPlan = old('plan', $sub?->plan->value ?? Plan::Basic);
    $defaultStatus = old('status', $sub?->status->value ?? \App\Share\Enums\SubscriptionStatus::Active);
    $expiresAtValue = old('expires_at');
    if ($expiresAtValue === null && $sub?->expires_at) {
        $expiresAtValue = $sub->expires_at->format('Y-m-d\TH:i');
    }
    $autoRenewChecked = old('auto_renew', $sub?->auto_renew ?? true);
    $selectedIds = array_map('intval', (array) $selectedProgramIds);
    $showProgramSelection = in_array($defaultPlan, [Plan::Basic, Plan::Plus], true);
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
        <li class="inline-block relative top-[3px] text-base text-primary-500 font-Inter">
            <a href="{{ route('admin.users.show', $user) }}">Chi tiết khách hàng #{{ $user->id }}</a>
            <iconify-icon icon="heroicons-outline:chevron-right" class="relative text-slate-500 text-sm rtl:rotate-180"></iconify-icon>
        </li>
        <li class="inline-block relative text-sm text-slate-500 font-Inter dark:text-white">
            {{ $sub ? 'Sửa subscription' : 'Tạo subscription' }}
        </li>
    </ul>
</div>
<!-- END: BreadCrumb -->

<div class="space-y-5">
    <div class="card">
        <header class="card-header noborder">
            <h4 class="card-title">{{ $sub ? 'Sửa subscription' : 'Tạo subscription' }}</h4>
            <p class="text-sm text-slate-500 mt-1">
                Khách hàng: {{ $user->first_name }} {{ $user->last_name }} ({{ $user->email }})
            </p>
        </header>
        <div class="card-body px-6 pb-6">
            <form action="{{ route('admin.users.subscription.update', $user) }}" method="POST" id="subscription-form">
                @csrf
                @method('PUT')

                <div class="input-area mb-5">
                    <label class="form-label" for="plan">Gói <span class="text-red-500">*</span></label>
                    <select name="plan" id="plan" class="form-control">
                        @foreach ($planOptions as $value => $label)
                            <option value="{{ $value }}" @selected($defaultPlan === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('plan')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                </div>

                <div class="input-area mb-5" id="program-selection-block" @if (! $showProgramSelection) hidden @endif>
                    <label class="form-label">Bộ môn <span class="text-red-500">*</span></label>
                    <p class="text-xs text-slate-500 mb-2" id="program-selection-hint"></p>
                    <div class="space-y-2">
                        @foreach ($programs as $program)
                            <label class="inline-flex items-center gap-2 cursor-pointer w-full">
                                <input type="checkbox" name="program_ids[]" value="{{ $program->id }}"
                                    class="form-checkbox program-checkbox"
                                    @checked(in_array($program->id, $selectedIds, true))>
                                <span class="text-sm text-slate-700 dark:text-white">{{ $program->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    @error('program_ids')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                    @error('program_ids.*')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                </div>

                <div class="input-area mb-5">
                    <label class="form-label" for="status">Trạng thái <span class="text-red-500">*</span></label>
                    <select name="status" id="status" class="form-control">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($defaultStatus === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('status')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                </div>

                <div class="input-area mb-5">
                    <label class="form-label" for="expires_at">Ngày hết hạn</label>
                    <input type="datetime-local" name="expires_at" id="expires_at" class="form-control"
                        value="{{ $expiresAtValue }}">
                    @error('expires_at')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                </div>

                <div class="input-area mb-5">
                    <label class="inline-flex items-center gap-2 cursor-pointer">
                        <input type="hidden" name="auto_renew" value="0">
                        <input type="checkbox" name="auto_renew" value="1" class="form-checkbox"
                            @checked(filter_var($autoRenewChecked, FILTER_VALIDATE_BOOLEAN))>
                        <span class="form-label mb-0">Tự gia hạn</span>
                    </label>
                    @error('auto_renew')<span class="text-danger-500 text-xs mt-1 block">{{ $message }}</span>@enderror
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn btn-dark inline-flex items-center gap-1 px-4">
                        <iconify-icon icon="heroicons-outline:check"></iconify-icon>
                        Lưu
                    </button>
                    <a href="{{ route('admin.users.show', $user) }}" class="btn btn-light inline-flex items-center gap-1 px-4">
                        Hủy
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const planSelect = document.getElementById('plan');
    const block = document.getElementById('program-selection-block');
    const hint = document.getElementById('program-selection-hint');
    const checkboxes = () => Array.from(document.querySelectorAll('.program-checkbox'));

    const maxByPlan = { basic: 1, plus: 2 };

    function updateProgramSelectionUi() {
        const plan = planSelect.value;
        const max = maxByPlan[plan] ?? 0;

        if (max === 0) {
            block.hidden = true;
            checkboxes().forEach((cb) => { cb.checked = false; cb.disabled = false; });
            return;
        }

        block.hidden = false;
        hint.textContent = max === 1
            ? 'Gói Basic: chọn đúng 1 bộ môn.'
            : 'Gói Plus: chọn tối đa 2 bộ môn.';

        const checked = checkboxes().filter((cb) => cb.checked);
        checkboxes().forEach((cb) => {
            cb.disabled = !cb.checked && checked.length >= max;
        });
    }

    planSelect.addEventListener('change', updateProgramSelectionUi);

    checkboxes().forEach((cb) => {
        cb.addEventListener('change', function () {
            const plan = planSelect.value;
            const max = maxByPlan[plan] ?? 0;
            if (max === 0) {
                return;
            }
            const checked = checkboxes().filter((c) => c.checked);
            if (checked.length > max) {
                this.checked = false;
            }
            updateProgramSelectionUi();
        });
    });

    updateProgramSelectionUi();
})();
</script>
@endpush
