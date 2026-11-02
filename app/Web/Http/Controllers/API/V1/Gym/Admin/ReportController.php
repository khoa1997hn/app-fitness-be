<?php

namespace App\Web\Http\Controllers\API\V1\Gym\Admin;

use App\Share\Exceptions\Gym\GymException;
use App\Share\Models\User;
use App\Share\Services\Gym\ReportService;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseAPIController;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GymReportPoint',
    required: ['date', 'value'],
    properties: [
        new OA\Property(property: 'date', type: 'string', format: 'date'),
        new OA\Property(property: 'value', type: 'integer', example: 1200000),
    ]
)]
#[OA\Schema(
    schema: 'GymReportFillStat',
    required: ['name', 'sessions', 'bookings', 'fill_rate_pct'],
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'Yoga Flow'),
        new OA\Property(property: 'sessions', description: 'Số buổi đã diễn ra', type: 'integer', example: 8),
        new OA\Property(property: 'bookings', type: 'integer', example: 42),
        new OA\Property(property: 'fill_rate_pct', type: 'number', example: 78.5),
    ]
)]
class ReportController extends BaseAPIController
{
    private const DEFAULT_RANGE_DAYS = 30;

    private const MAX_RANGE_DAYS = 366;

    public function __construct(private readonly ReportService $reportService) {}

    #[OA\Get(
        path: '/admin/dashboard',
        description: 'Thẻ số liệu theo vai trò: admin thấy số liệu vận hành, lễ tân thấy việc trong ngày, HLV chỉ thấy lớp của mình. Quyền `dashboard.view`.',
        summary: 'Tổng quan theo vai trò',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Reports'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', type: 'array', items: new OA\Items(properties: [
                    new OA\Property(property: 'label', type: 'string', example: 'Lớp hôm nay'),
                    new OA\Property(property: 'value', type: 'number', example: 4),
                    new OA\Property(property: 'note', type: 'string', example: 'Hôm nay'),
                    new OA\Property(property: 'format', type: 'string', enum: ['number', 'currency', 'percent'], example: 'number'),
                ], type: 'object')),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
        ]
    )]
    public function dashboard(): JsonResponse
    {
        /** @var User $user */
        $user = auth()->user();

        return ResponseAPI::success($this->reportService->dashboard($user));
    }

    #[OA\Get(
        path: '/admin/reports/overview',
        description: 'Doanh thu, học viên mới, lượt đặt, tỷ lệ hủy trong kỳ. Mặc định 30 ngày gần nhất, tối đa 366 ngày. Quyền `reports.view`.',
        summary: 'Báo cáo: tổng quan',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Reports'],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'total_revenue', type: 'integer', example: 45000000),
                    new OA\Property(property: 'revenue_by_type', type: 'object', example: ['package' => 30000000, 'therapy' => 5000000, 'extra_people' => 0, 'event' => 10000000, 'redlight' => 0]),
                    new OA\Property(property: 'new_members', description: 'Số người mua gói đầu tiên trong kỳ', type: 'integer', example: 24),
                    new OA\Property(property: 'bookings', type: 'integer', example: 127),
                    new OA\Property(property: 'cancellation_rate', type: 'number', example: 8.5),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Khoảng thời gian không hợp lệ'),
        ]
    )]
    public function overview(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return ResponseAPI::success($this->reportService->overview($from, $to));
    }

    #[OA\Get(
        path: '/admin/reports/revenue',
        description: 'Doanh thu theo ngày và theo từng sản phẩm, chỉ tính đơn đã thanh toán. Quyền `reports.view`.',
        summary: 'Báo cáo: doanh thu',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Reports'],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'series', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymReportPoint')),
                    new OA\Property(property: 'breakdown', type: 'array', items: new OA\Items(properties: [
                        new OA\Property(property: 'type', type: 'string', enum: ['package', 'therapy', 'extra_people', 'event', 'redlight']),
                        new OA\Property(property: 'name', type: 'string', example: '10 classes'),
                        new OA\Property(property: 'quantity_sold', type: 'integer', example: 15),
                        new OA\Property(property: 'revenue', type: 'integer', example: 33000000),
                    ], type: 'object')),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Khoảng thời gian không hợp lệ'),
        ]
    )]
    public function revenue(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return ResponseAPI::success($this->reportService->revenue($from, $to));
    }

    #[OA\Get(
        path: '/admin/reports/members',
        description: 'Học viên mới theo ngày, tỷ lệ mua lại, tỷ lệ hủy lịch. Tỷ lệ mua lại là phần người mua gói trong kỳ đã từng mua gói trước đó. Quyền `reports.view`.',
        summary: 'Báo cáo: học viên',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Reports'],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'series', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymReportPoint')),
                    new OA\Property(property: 'new_members', type: 'integer', example: 24),
                    new OA\Property(property: 'buyers', type: 'integer', example: 40),
                    new OA\Property(property: 'renewal_rate', type: 'number', example: 40.0),
                    new OA\Property(property: 'cancellation_rate', type: 'number', example: 8.5),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Khoảng thời gian không hợp lệ'),
        ]
    )]
    public function members(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return ResponseAPI::success($this->reportService->members($from, $to));
    }

    #[OA\Get(
        path: '/admin/reports/classes',
        description: 'Lượt đặt và tỷ lệ lấp đầy theo lớp, theo HLV. Chỉ tính buổi đã diễn ra. Quyền `reports.view`.',
        summary: 'Báo cáo: lớp học',
        security: [['bearerAuth' => []]],
        tags: ['Gym Admin Reports'],
        parameters: [
            new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [
                new OA\Property(property: 'success', type: 'boolean', example: true),
                new OA\Property(property: 'message', type: 'string', example: 'Success'),
                new OA\Property(property: 'data', properties: [
                    new OA\Property(property: 'classes', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymReportFillStat')),
                    new OA\Property(property: 'trainers', type: 'array', items: new OA\Items(ref: '#/components/schemas/GymReportFillStat')),
                ], type: 'object'),
            ])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 422, description: 'Khoảng thời gian không hợp lệ'),
        ]
    )]
    public function classes(Request $request): JsonResponse
    {
        [$from, $to] = $this->range($request);

        return ResponseAPI::success($this->reportService->classes($from, $to));
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $validated = $request->validate([
            'from' => 'nullable|date_format:Y-m-d',
            'to' => 'nullable|date_format:Y-m-d',
        ]);

        $to = isset($validated['to']) ? Carbon::parse($validated['to'])->endOfDay() : now()->endOfDay();
        $from = isset($validated['from'])
            ? Carbon::parse($validated['from'])->startOfDay()
            : $to->copy()->subDays(self::DEFAULT_RANGE_DAYS - 1)->startOfDay();

        if ($from->gt($to) || $from->diffInDays($to) > self::MAX_RANGE_DAYS) {
            throw new GymException(__('messages.gym_report_range_invalid', ['days' => self::MAX_RANGE_DAYS]));
        }

        return [$from, $to];
    }
}
