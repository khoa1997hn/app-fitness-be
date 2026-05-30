<?php

namespace App\Admin\Http\Controllers;

use App\Share\Http\Controllers\Concerns\HandlesPresignedFileUpload;
use App\Share\Http\Controllers\Controller as BaseController;
use App\Share\Http\Requests\PresignedFileUploadRequest;
use App\Share\Services\File\FileUploadService;
use Illuminate\Http\JsonResponse;

class FileController extends BaseController
{
    use HandlesPresignedFileUpload;

    public function presignedUpload(PresignedFileUploadRequest $request, FileUploadService $fileUploadService): JsonResponse
    {
        return $this->presignedFileUpload($request, $fileUploadService);
    }
}
