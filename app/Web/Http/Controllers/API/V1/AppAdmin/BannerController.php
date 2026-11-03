<?php

namespace App\Web\Http\Controllers\API\V1\AppAdmin;

use App\Admin\Http\Requests\StoreBannerRequest;
use App\Admin\Http\Requests\UpdateBannerRequest;
use App\Share\Models\Banner;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseController;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class BannerController extends BaseController
{
    use MapsContent;

    #[OA\Get(
        path: '/admin/app/banners',
        summary: 'Danh sách Banner',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminBannerPage')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function index()
    {
        $banners = Banner::query()->with(['translations'])->orderByDesc('id')->paginate(20);

        return ResponseAPI::success($this->page($banners, fn (Banner $banner) => $this->mapBanner($banner)));
    }

    #[OA\Get(
        path: '/admin/app/banners/{banner}',
        summary: 'Chi tiết Banner',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'banner', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminBanner')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function show(Banner $banner)
    {
        $banner->load(['translations']);

        return ResponseAPI::success($this->mapBanner($banner));
    }

    #[OA\Post(
        path: '/admin/app/banners',
        summary: 'Tạo Banner',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminBannerInput')),
        responses: [
            new OA\Response(response: 201, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminBanner')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(StoreBannerRequest $request)
    {
        $validated = $request->validated();

        $banner = new Banner;
        $banner->description = $validated['description'] ?? null;
        $this->fillTranslations($banner, $validated);
        $banner->save();

        return ResponseAPI::success($this->mapBanner($banner->load('translations')), code: 201);
    }

    #[OA\Put(
        path: '/admin/app/banners/{banner}',
        summary: 'Cập nhật Banner',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'banner', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminBannerInput')),
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    #[OA\Patch(
        path: '/admin/app/banners/{banner}',
        summary: 'Cập nhật Banner',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'banner', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminBannerInput')),
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function update(UpdateBannerRequest $request, Banner $banner)
    {
        $validated = $request->validated();

        $banner->description = $validated['description'] ?? null;
        $this->fillTranslations($banner, $validated);
        $banner->save();

        return ResponseAPI::success();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillTranslations(Banner $banner, array $validated): void
    {
        $requiredLocale = (string) config('translatable.fallback_locale');

        foreach ((array) config('translatable.locales') as $locale) {
            $data = $validated['translations'][$locale] ?? null;
            $imagePath = $data['image']['path'] ?? null;

            // Skip non-required locale if image is missing (image is NOT NULL in DB)
            if ($locale !== $requiredLocale && empty($imagePath)) {
                continue;
            }

            $banner->fill([
                $locale => [
                    'image' => $data['image'] ?? null,
                    'link_url' => $data['link_url'] ?? null,
                    'order' => $data['order'] ?? 0,
                    'is_active' => isset($data['is_active']) ? (bool) $data['is_active'] : true,
                ],
            ]);
        }
    }

    #[OA\Delete(
        path: '/admin/app/banners/{banner}',
        summary: 'Xóa Banner',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'banner', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function destroy(Banner $banner)
    {
        $banner->delete();

        return ResponseAPI::success();
    }
}
