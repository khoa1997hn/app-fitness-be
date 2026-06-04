<?php

namespace App\Admin\Http\Controllers;

use App\Share\Enums\Plan;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Models\AppleSubscription;
use App\Share\Models\GoogleSubscription;
use App\Share\Models\User;
use App\Share\Services\Subscription\SubscriptionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use League\Csv\Writer;

class UserController extends BaseController
{
    public function index(Request $request)
    {
        $query = $this->applyFilters(User::query(), $request);

        $users = $query->latest()->orderByDesc('id')->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function show(User $user)
    {
        $user->load('subscription');

        $googleTransactions = GoogleSubscription::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($t) => [
                'provider' => 'Google IAP',
                'transaction_id' => $t->order_id,
                'product_id' => $t->item_id,
                'purchase_date' => $t->transaction_date,
                'expires_date' => $t->expiry_date,
                'status' => $t->status?->value,
                'created_at' => $t->created_at,
            ]);

        $appleTransactions = AppleSubscription::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($t) => [
                'provider' => 'Apple IAP',
                'transaction_id' => $t->transaction_id,
                'product_id' => $t->product_id,
                'purchase_date' => $t->purchase_date,
                'expires_date' => $t->expires_date,
                'status' => $t->status?->value,
                'created_at' => $t->created_at,
            ]);

        $transactions = $googleTransactions->concat($appleTransactions)
            ->sortByDesc('created_at')
            ->values();

        return view('admin.users.show', compact('user', 'transactions'));
    }

    public function editSubscription(User $user)
    {
        $user->load('subscription');

        $planOptions = Plan::asSelectArray();
        $statusOptions = SubscriptionStatus::asSelectArray();

        return view('admin.users.subscription.edit', compact('user', 'planOptions', 'statusOptions'));
    }

    public function updateSubscription(Request $request, User $user, SubscriptionService $subscriptionService)
    {
        $validated = $request->validate([
            'plan' => ['required', 'in:'.implode(',', Plan::getValues())],
            'status' => ['required', 'in:'.implode(',', SubscriptionStatus::getValues())],
            'expires_at' => ['nullable', 'date'],
            'auto_renew' => ['boolean'],
        ], [
            'plan.required' => 'Vui lòng chọn gói.',
            'plan.in' => 'Gói không hợp lệ.',
            'status.required' => 'Vui lòng chọn trạng thái.',
            'status.in' => 'Trạng thái không hợp lệ.',
            'expires_at.date' => 'Ngày hết hạn không hợp lệ.',
            'auto_renew.boolean' => 'Tự gia hạn không hợp lệ.',
        ]);

        $expiresAt = isset($validated['expires_at'])
            ? Carbon::parse($validated['expires_at'])
            : null;

        $hadSubscription = $user->subscription()->exists();

        $subscriptionService->adminUpsert(
            $user,
            $validated['plan'],
            $validated['status'],
            $expiresAt,
            $request->boolean('auto_renew'),
        );

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', $hadSubscription ? 'Cập nhật subscription thành công.' : 'Tạo subscription thành công.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Xóa khách hàng thành công.');
    }

    public function export(Request $request)
    {
        $query = $this->applyFilters(User::query(), $request);

        $csv = Writer::fromString();
        $csv->setEscape('');

        $query->select(['id', 'email', 'name', 'phone', 'referral_email', 'created_at'])
            ->orderBy('id')
            ->chunkById(200, function ($users) use ($csv) {
                foreach ($users as $user) {
                    $csv->insertOne([
                        $user->email,
                        $user->name,
                        $user->phone ?? '',
                        $user->referral_email ?? '',
                        $user->created_at->format('Y-m-d H:i:s'),
                    ]);
                }
            });

        return $csv->download('users.csv');
    }

    private function applyFilters($query, Request $request)
    {
        if ($keyword = $request->input('q')) {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%");
            });
        }

        if ($startDate = $request->input('start_date')) {
            $query->whereDate('created_at', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        return $query;
    }
}
