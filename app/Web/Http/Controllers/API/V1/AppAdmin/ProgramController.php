<?php

namespace App\Web\Http\Controllers\API\V1\AppAdmin;

use App\Admin\Http\Requests\UpdateProgramRequest;
use App\Share\Models\Program;
use App\Share\Utils\ResponseAPI;
use App\Web\Http\Controllers\API\V1\APIController as BaseController;
use OpenApi\Attributes as OA;

class ProgramController extends BaseController
{
    use MapsContent;

    #[OA\Get(
        path: '/admin/app/programs',
        summary: 'Danh sách Program',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/AppAdminProgram'))])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function index()
    {
        $programs = Program::query()->with(['translations', 'lessons.videos.translations'])
            ->withCount(['favorites', 'lessons'])->orderBy('id')->get();

        return ResponseAPI::success($programs->map(fn (Program $program) => $this->mapProgram($program))->all());
    }

    #[OA\Get(
        path: '/admin/app/programs/{program}',
        summary: 'Chi tiết Program',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'program', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', ref: '#/components/schemas/AppAdminProgramDetail')])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function show(Program $program)
    {
        $program->load(['translations', 'lessons' => fn ($query) => $query->with(['translations', 'videos.translations'])->withCount('favorites')->orderBy('id')])
            ->loadCount(['favorites', 'lessons']);

        return ResponseAPI::success($this->mapProgram($program, true));
    }

    #[OA\Put(
        path: '/admin/app/programs/{program}',
        summary: 'Cập nhật Program',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'program', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminProgramInput')),
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    #[OA\Patch(
        path: '/admin/app/programs/{program}',
        summary: 'Cập nhật Program',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'program', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: '#/components/schemas/AppAdminProgramInput')),
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function update(UpdateProgramRequest $request, Program $program)
    {
        $validated = $request->validated();

        $program->fill([
            'rating' => $validated['rating'] ?? null,
        ]);

        foreach ((array) config('translatable.locales') as $locale) {
            $data = $validated['translations'][$locale] ?? null;

            if ($data === null || ($data['name'] ?? null) === null) {
                continue;
            }

            $program->fill([
                $locale => [
                    'name' => $data['name'],
                    'description' => $data['description'] ?? null,
                    'cover' => $data['cover'] ?? null,
                    'sort' => $data['sort'] ?? 0,
                ],
            ]);
        }

        $program->save();

        return ResponseAPI::success();
    }

    #[OA\Delete(
        path: '/admin/app/programs/{program}',
        summary: 'Xóa Program',
        description: 'Quản trị app video. Chỉ admin; JWT và permission app.manage.',
        security: [['bearerAuth' => []]],
        tags: ['App Admin'],
        parameters: [new OA\Parameter(name: 'program', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Thành công', content: new OA\JsonContent(properties: [new OA\Property(property: 'success', type: 'boolean'), new OA\Property(property: 'message', type: 'string'), new OA\Property(property: 'data', type: 'object', nullable: true, example: null)])),
            new OA\Response(response: 401, description: 'Chưa đăng nhập'),
            new OA\Response(response: 403, description: 'Không có quyền'),
            new OA\Response(response: 404, description: 'Không tìm thấy'),
            new OA\Response(response: 422, description: 'Dữ liệu không hợp lệ'),
        ]
    )]
    public function destroy(Program $program)
    {
        $program->delete();

        return ResponseAPI::success();
    }
}
