<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Enums\BookingRequestStatus;
use App\Share\Enums\BookingStatus;
use App\Share\Enums\PackageType;
use App\Share\Models\Booking;
use App\Share\Models\PrivateRequest;
use App\Share\Models\User;
use App\Share\Models\UserPackage;
use App\Share\Services\Gym\BookingService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymBookingMember',
    required: ['id', 'member', 'package_name', 'completed_count', 'status'],
    properties: [
        new OA\Property(property: 'id', description: 'ID gói đã mua', type: 'integer', example: 7),
        new OA\Property(property: 'member', properties: [
            new OA\Property(property: 'id', type: 'integer', example: 4),
            new OA\Property(property: 'full_name', type: 'string', example: 'Phạm Thị Member'),
        ], type: 'object'),
        new OA\Property(property: 'package_name', type: 'string', example: '10 classes'),
        new OA\Property(property: 'package_type', type: 'string', example: 'class_pack'),
        new OA\Property(property: 'total_credits', type: 'integer', example: 10, nullable: true),
        new OA\Property(property: 'remaining_credits', type: 'integer', example: 8, nullable: true),
        new OA\Property(property: 'completed_count', description: 'Số buổi đã qua giờ', type: 'integer', example: 2),
        new OA\Property(property: 'end_date', type: 'string', format: 'date', example: '2027-03-17'),
        new OA\Property(property: 'status', type: 'string', enum: ['active', 'completed', 'expired'], example: 'active'),
    ]
)]
class BookingController extends BaseAPIController
{
    public function __construct(
        private readonly BookingService $bookingService,
    ) {}

    #[OA\Get(
        path: '/admin/bookings/members',
        description: 'Học viên gói thường hoặc Private, theo từng gói đã mua. Quyền `bookings.manage`.',
        summary: 'Admin: học viên theo gói',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Bookings'],
        parameters: [
            new OA\Parameter(name: 'q', description: 'Tìm theo tên học viên, tên gói', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'group', description: 'Mặc định `regular`', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['regular', 'private'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymBookingMember')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function members(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'q' => 'nullable|string|max:255',
            'group' => 'nullable|in:regular,private',
        ]);
        $privateTypes = [PackageType::PrivatePack, PackageType::PrivateGroup];

        $packages = UserPackage::query()
            ->with('user')
            ->withCount($this->completedCounts())
            ->when(
                ($validated['group'] ?? 'regular') === 'private',
                fn ($query) => $query->whereIn('package_type', $privateTypes),
                fn ($query) => $query->whereNotIn('package_type', $privateTypes),
            )
            ->when($validated['q'] ?? null, function ($query, string $q) {
                $like = "%{$q}%";
                $query->where(fn ($inner) => $inner
                    ->where('name', 'like', $like)
                    ->orWhereHas('user', fn ($user) => $user->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", [$like])));
            })
            ->orderByDesc('id')
            ->get();

        return ResponseAPI::success($packages->map(fn (UserPackage $package) => $this->mapMember($package))->all());
    }

    #[OA\Get(
        path: '/admin/bookings/members/{userPackage}',
        description: 'Chi tiết gói đã mua và các buổi đã đặt. Quyền `bookings.manage`.',
        summary: 'Admin: chi tiết học viên gói thường',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Bookings'],
        parameters: [
            new OA\Parameter(name: 'userPackage', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 7)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', allOf: [
                    new OA\Schema(ref: '#/components/schemas/GymBookingMember'),
                    new OA\Schema(properties: [
                        new OA\Property(property: 'bookings', type: 'array', items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 21),
                            new OA\Property(property: 'class_name', type: 'string', example: 'Yoga Flow'),
                            new OA\Property(property: 'trainer_name', type: 'string', example: 'Nguyễn Thu Hà'),
                            new OA\Property(property: 'start_time', type: 'string', example: '2026-11-05T07:00:00'),
                            new OA\Property(property: 'status', type: 'string', enum: ['confirmed', 'cancelled'], example: 'confirmed'),
                            new OA\Property(property: 'has_redlight', description: 'Dùng lượt Redlight; lịch đã hủy thì lượt đã được hoàn', type: 'boolean', example: false),
                            new OA\Property(property: 'is_finished', description: 'Lớp đã qua giờ', type: 'boolean', example: false),
                        ], type: 'object')),
                        new OA\Property(property: 'private_requests', type: 'array', items: new OA\Items(properties: [
                            new OA\Property(property: 'id', type: 'integer', example: 5),
                            new OA\Property(property: 'discipline', type: 'string', example: 'Yoga'),
                            new OA\Property(property: 'requested_at', type: 'string', example: '2026-11-20T15:00:00'),
                            new OA\Property(property: 'status', type: 'string', enum: ['pending', 'confirmed', 'rejected', 'cancelled', 'completed'], example: 'pending'),
                        ], type: 'object')),
                    ]),
                ]),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
        ]
    )]
    public function member(UserPackage $userPackage): JsonResponse
    {
        $userPackage->load(['user', 'bookings.classSchedule.fitnessClass', 'bookings.classSchedule.trainer', 'privateRequests'])
            ->loadCount($this->completedCounts());

        return ResponseAPI::success([
            ...$this->mapMember($userPackage),
            'bookings' => $userPackage->bookings
                ->sortByDesc(fn (Booking $booking) => $booking->classSchedule->start_time)
                ->values()
                ->map(fn (Booking $booking) => [
                    'id' => $booking->id,
                    'class_name' => $booking->classSchedule->fitnessClass->name,
                    'trainer_name' => trim($booking->classSchedule->trainer->first_name.' '.$booking->classSchedule->trainer->last_name),
                    'start_time' => $booking->classSchedule->start_time->format('Y-m-d\TH:i:s'),
                    'status' => $booking->status,
                    'is_finished' => $booking->classSchedule->end_time->lte(now()),
                    'has_redlight' => $booking->has_redlight,
                ])
                ->all(),
            'private_requests' => $userPackage->privateRequests
                ->sortByDesc('id')
                ->values()
                ->map(fn (PrivateRequest $request) => [
                    'id' => $request->id,
                    'discipline' => $request->discipline,
                    'requested_at' => $request->requested_at->format('Y-m-d\TH:i:s'),
                    'status' => $request->status,
                ])
                ->all(),
        ]);
    }

    #[OA\Patch(
        path: '/admin/bookings/{booking}/cancel',
        description: 'Hủy lịch thay học viên, hoàn buổi, báo trong tài khoản và gửi email cho học viên. Lớp đã qua giờ thì không hủy được. Quyền `bookings.cancel_class`.',
        summary: 'Admin: hủy lịch học viên',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Bookings'],
        parameters: [
            new OA\Parameter(name: 'booking', in: 'path', required: true, schema: new OA\Schema(type: 'integer', example: 21)),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Đã hủy', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'string', example: null, nullable: true),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Đã hủy hoặc lớp đã qua giờ'),
        ]
    )]
    public function cancel(Booking $booking): JsonResponse
    {
        /** @var User $staff */
        $staff = auth()->user();
        $this->bookingService->cancelByStaff($booking, $staff);

        return ResponseAPI::success();
    }

    /**
     * @return array<string, \Closure>
     */
    private function completedCounts(): array
    {
        return [
            'bookings as completed_bookings_count' => fn ($query) => $query
                ->where('status', BookingStatus::Confirmed)
                ->whereHas('classSchedule', fn ($schedule) => $schedule->where('end_time', '<=', now())),
            'privateRequests as completed_private_count' => fn ($query) => $query
                ->where('status', BookingRequestStatus::Completed),
        ];
    }

    /**
     * Cần load `user` và withCount `completedCounts()`.
     *
     * @return array<string, mixed>
     */
    private function mapMember(UserPackage $package): array
    {
        $status = 'active';
        if ($package->end_date->endOfDay()->isPast()) {
            $status = 'expired';
        } elseif ($package->remaining_credits === 0) {
            $status = 'completed';
        }

        return [
            'id' => $package->id,
            'member' => [
                'id' => $package->user->id,
                'full_name' => trim($package->user->first_name.' '.$package->user->last_name),
            ],
            'package_name' => $package->name,
            'package_type' => $package->package_type,
            'total_credits' => $package->total_credits,
            'remaining_credits' => $package->remaining_credits,
            'completed_count' => $package->completed_bookings_count + $package->completed_private_count,
            'end_date' => $package->end_date->toDateString(),
            'status' => $status,
        ];
    }
}
