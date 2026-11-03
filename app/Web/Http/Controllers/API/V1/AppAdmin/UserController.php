<?php

namespace App\Web\Http\Controllers\API\V1\AppAdmin;

use App\Share\Enums\Plan;
use App\Share\Enums\RegistrationSource;
use App\Share\Enums\SubscriptionStatus;
use App\Share\Models\AppleSubscription;
use App\Share\Models\GoogleSubscription;
use App\Share\Models\User;
use App\Share\Services\Gym\UserAccountService;
use App\Share\Services\Program\ProgramSelectionService;
use App\Share\Services\Subscription\SubscriptionService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use League\Csv\Writer;
use OpenApi\Attributes as OA;

class UserController extends APIController
{
    use MapsContent;

    #[OA\Get(
        path: '/admin/app/users',
        summary: 'Danh sách tài khoản',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'registration_source', in: 'query', required: false, schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminUserPage')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function index(Request $request)
    {
        $users = $this->filteredUsers($request)->with('creator')->latest()->orderByDesc('id')->paginate(10);

        return ResponseAPI::success($this->page($users, fn (User $user) => $this->mapUser($user)));
    }

    #[OA\Get(
        path: '/admin/app/users/{user}',
        summary: 'Chi tiết tài khoản, subscription và lịch sử IAP',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminUserDetail')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function show(User $user)
    {
        $user->load(['creator', 'subscription.programSelections']);
        $subscription = $user->subscription;
        $google = GoogleSubscription::query()->where('user_id', $user->id)->latest()->get()->map(fn ($transaction) => [
            'provider' => 'Google IAP',
            'transaction_id' => $transaction->order_id,
            'product_id' => $transaction->item_id,
            'purchase_date' => $transaction->transaction_date,
            'expires_date' => $transaction->expiry_date,
            'status' => $transaction->status,
            'created_at' => $transaction->created_at,
        ]);
        $apple = AppleSubscription::query()->where('user_id', $user->id)->latest()->get()->map(fn ($transaction) => [
            'provider' => 'Apple IAP',
            'transaction_id' => $transaction->transaction_id,
            'product_id' => $transaction->product_id,
            'purchase_date' => $transaction->purchase_date,
            'expires_date' => $transaction->expires_date,
            'status' => $transaction->status,
            'created_at' => $transaction->created_at,
        ]);

        return ResponseAPI::success([
            ...$this->mapUser($user),
            'subscription' => $subscription ? [
                'id' => $subscription->id,
                'plan' => $subscription->plan,
                'status' => $subscription->status,
                'provider' => $subscription->provider,
                'expires_at' => $subscription->expires_at,
                'auto_renew' => $subscription->auto_renew,
                'program_ids' => $subscription->programSelections->pluck('program_id')->all(),
                'amount' => $subscription->amount,
                'currency' => $subscription->currency,
                'billing_cycle' => $subscription->billing_cycle,
                'trial_ends_at' => $subscription->trial_ends_at,
                'cancelled_at' => $subscription->cancelled_at,
                'grace_period_ends_at' => $subscription->grace_period_ends_at,
                'created_at' => $subscription->created_at,
            ] : null,
            'transactions' => $google->concat($apple)->sortByDesc('created_at')->values()->all(),
        ]);
    }

    #[OA\Put(
        path: '/admin/app/users/{user}/subscription',
        summary: 'Cấp hoặc sửa subscription',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminSubscriptionInput')),
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminUserDetail')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function updateSubscription(Request $request, User $user, SubscriptionService $subscriptions, ProgramSelectionService $selections)
    {
        $requiresPrograms = in_array($request->input('plan'), [Plan::Basic, Plan::Plus], true);
        $validated = $request->validate([
            'plan' => ['required', Rule::in(Plan::getValues())],
            'status' => ['required', Rule::in(SubscriptionStatus::getValues())],
            'expires_at' => ['nullable', 'date'],
            'auto_renew' => ['sometimes', 'boolean'],
            'program_ids' => [Rule::requiredIf($requiresPrograms), 'array', Rule::when($requiresPrograms, ['min:1'])],
            'program_ids.*' => ['integer', 'distinct'],
        ]);

        DB::transaction(function () use ($validated, $request, $user, $subscriptions, $selections) {
            $subscription = $subscriptions->adminUpsert(
                $user, $validated['plan'], $validated['status'],
                isset($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null,
                $request->boolean('auto_renew'),
            );
            $selections->adminSyncSelections($subscription, $user, $validated['plan'], array_map('intval', $validated['program_ids'] ?? []));
        });

        return $this->show($user->fresh());
    }

    #[OA\Delete(
        path: '/admin/app/users/{user}',
        summary: 'Xóa tài khoản',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function destroy(User $user, UserAccountService $accounts)
    {
        $accounts->assertNotSelf(auth()->user(), $user);
        $accounts->assertDeletable($user);
        $user->delete();

        return ResponseAPI::success();
    }

    #[OA\Get(
        path: '/admin/app/users/export',
        summary: 'Xuất CSV tài khoản',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'q', in: 'query', required: false, schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'start_date', in: 'query', required: false, schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'end_date', in: 'query', required: false, schema: new OA\Schema(type: 'string')), new OA\Parameter(name: 'registration_source', in: 'query', required: false, schema: new OA\Schema(type: 'string'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\MediaType(mediaType: 'text/csv', schema: new OA\Schema(type: 'string'))),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function export(Request $request)
    {
        $csv = Writer::fromString();
        $csv->setEscape('');
        $csv->insertOne(['Email', 'Họ và tên', 'Số điện thoại', 'Nguồn tạo', 'Người tạo', 'Ngày tạo']);
        $this->filteredUsers($request)->with('creator')->orderBy('id')->chunkById(200, function ($users) use ($csv) {
            foreach ($users as $user) {
                $row = [$user->email, trim($user->first_name.' '.$user->last_name), $user->phone ?? '', $user->registration_source?->value ?? '', $user->creator ? trim($user->creator->first_name.' '.$user->creator->last_name) : '', $user->created_at->format('Y-m-d H:i:s')];
                $csv->insertOne(array_map(fn ($value) => preg_match('/^[=+@\-\t\r\n]/u', $value) ? "'".$value : $value, $row));
            }
        });

        return response((string) $csv, 200, ['Content-Type' => 'text/csv; charset=UTF-8', 'Content-Disposition' => 'attachment; filename="users.csv"']);
    }

    private function filteredUsers(Request $request)
    {
        $validated = $request->validate([
            'q' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d'],
            'registration_source' => ['nullable', Rule::in(RegistrationSource::getValues())],
        ]);
        $query = User::query();
        if (! empty($validated['q'])) {
            $keyword = $validated['q'];
            $query->where(fn ($query) => $query->where('first_name', 'like', "%{$keyword}%")->orWhere('last_name', 'like', "%{$keyword}%")->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$keyword}%"])->orWhere('email', 'like', "%{$keyword}%")->orWhere('phone', 'like', "%{$keyword}%"));
        }
        foreach (['start_date' => '>=', 'end_date' => '<='] as $key => $operator) {
            if (! empty($validated[$key])) {
                $query->whereDate('created_at', $operator, $validated[$key]);
            }
        }
        if (! empty($validated['registration_source'])) {
            $query->where('registration_source', $validated['registration_source']);
        }

        return $query;
    }

    private function mapUser(User $user): array
    {
        return [
            'id' => $user->id,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => trim($user->first_name.' '.$user->last_name),
            'phone' => $user->phone,
            'dob' => $user->dob?->toDateString(),
            'role' => $user->role,
            'status' => $user->status,
            'plan' => $user->plan,
            'subscription_status' => $user->subscription_status,
            'created_at' => $user->created_at,
            ...$user->registrationProvenance(),
        ];
    }
}
