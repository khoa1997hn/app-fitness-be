<?php

namespace App\Share\Http\Controllers\Concerns;

use App\Share\Http\Requests\PresignedFileUploadRequest;
use App\Share\Services\File\FileUploadService;
use App\Share\Utils\ResponseAPI;
use Illuminate\Http\JsonResponse;

trait HandlesPresignedFileUpload
{
    protected function presignedFileUpload(PresignedFileUploadRequest $request, FileUploadService $fileUploadService): JsonResponse
    {
        $validated = $request->validated();

        return ResponseAPI::success(
            $fileUploadService->createPresignedUpload(
                $validated['type'],
                $validated['filename'],
                $validated['mimetype'],
                (int) $validated['size'],
            )
        );
    }
}
