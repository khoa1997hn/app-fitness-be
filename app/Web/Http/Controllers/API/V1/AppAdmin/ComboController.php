<?php

namespace App\Web\Http\Controllers\API\V1\AppAdmin;

use App\Admin\Http\Requests\StoreComboRequest;
use App\Admin\Http\Requests\UpdateComboRequest;
use App\Share\Models\Combo;
use App\Share\Models\ComboInfo;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseController;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ComboController extends BaseController
{
    use MapsContent;

    #[OA\Get(
        path: '/admin/app/combos',
        summary: 'Danh sách Combo',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminComboPage')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function index()
    {
        $combos = Combo::query()->with(['translations', 'programs', 'infos.translations'])->orderByDesc('id')->paginate(20);

        return ResponseAPI::success($this->page($combos, fn (Combo $combo) => $this->mapCombo($combo)));
    }

    #[OA\Get(
        path: '/admin/app/combos/{combo}',
        summary: 'Chi tiết Combo',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'combo', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminCombo')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function show(Combo $combo)
    {
        $combo->load(['translations', 'programs', 'infos.translations']);

        return ResponseAPI::success($this->mapCombo($combo));
    }

    #[OA\Post(
        path: '/admin/app/combos',
        summary: 'Tạo Combo',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminComboInput')),
        responses: [
            new OA\Response(response: 201, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminCombo')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function store(StoreComboRequest $request)
    {
        $validated = $request->validated();

        $combo = DB::transaction(function () use ($validated) {
            $combo = new Combo;
            $this->fillTranslations($combo, $validated);
            $combo->save();

            $this->syncPrograms($combo, $validated['program_ids']);
            $this->syncInfos($combo, $validated['infos'] ?? []);

            return $combo;
        });

        return ResponseAPI::success($this->mapCombo($combo->load(['translations', 'programs', 'infos.translations'])), code: 201);
    }

    #[OA\Put(
        path: '/admin/app/combos/{combo}',
        summary: 'Cập nhật Combo',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'combo', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminComboInput')),
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    #[OA\Patch(
        path: '/admin/app/combos/{combo}',
        summary: 'Cập nhật Combo',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'combo', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminComboInput')),
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function update(UpdateComboRequest $request, Combo $combo)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($combo, $validated) {
            $this->fillTranslations($combo, $validated);
            $combo->save();

            $this->syncPrograms($combo, $validated['program_ids']);
            $combo->infos()->delete();
            $this->syncInfos($combo, $validated['infos'] ?? []);
        });

        return ResponseAPI::success();
    }

    #[OA\Delete(
        path: '/admin/app/combos/{combo}',
        summary: 'Xóa Combo',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'combo', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function destroy(Combo $combo)
    {
        $combo->delete();

        return ResponseAPI::success();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function fillTranslations(Combo $combo, array $validated): void
    {
        $requiredLocale = (string) config('translatable.fallback_locale');

        foreach ((array) config('translatable.locales') as $locale) {
            $data = $validated['translations'][$locale] ?? null;
            $name = $data['name'] ?? null;
            $coverPath = $data['cover']['path'] ?? null;

            if ($locale !== $requiredLocale && empty($name) && empty($coverPath)) {
                continue;
            }

            $combo->fill([
                $locale => [
                    'name' => $name,
                    'cover' => $this->normalizeFileInput($data['cover'] ?? null),
                ],
            ]);
        }
    }

    /**
     * @param  list<int>  $programIds
     */
    private function syncPrograms(Combo $combo, array $programIds): void
    {
        $syncData = [];

        foreach (array_values($programIds) as $index => $programId) {
            $syncData[(int) $programId] = ['sort' => $index];
        }

        $combo->programs()->sync($syncData);
    }

    /**
     * @param  list<array<string, mixed>>  $infos
     */
    private function syncInfos(Combo $combo, array $infos): void
    {
        $requiredLocale = (string) config('translatable.fallback_locale');

        foreach (array_values($infos) as $index => $infoData) {
            $info = new ComboInfo([
                'combo_id' => $combo->id,
                'sort' => $index,
                'icon' => $this->normalizeFileInput($infoData['icon'] ?? null),
            ]);
            $info->save();

            foreach ((array) config('translatable.locales') as $locale) {
                $text = $infoData['translations'][$locale]['text'] ?? null;

                if ($locale !== $requiredLocale && empty($text)) {
                    continue;
                }

                $info->fill([
                    $locale => [
                        'text' => $text,
                    ],
                ]);
            }

            $info->save();
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function normalizeFileInput(mixed $file): ?array
    {
        if (! is_array($file) || empty($file['path'])) {
            return null;
        }

        return $file;
    }
}
