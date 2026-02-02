<?php

namespace App\Share\Services\File;

use App\Share\Attributes\File;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

readonly class FileUploadService
{
    public function __construct(
        private FileConfigService $fileConfigService
    ) {}

    /**
     * Upload file và trả về thông tin file
     */
    public function upload(UploadedFile $file, string $type): File
    {
        $config = $this->fileConfigService->getConfig($type);
        if (! isset($config['prefix_path'])) {
            throw new \InvalidArgumentException("File type '{$type}' is not configured with prefix_path");
        }
        $filename = $this->generateFilename($file);
        $path = $file->storeAs($config['prefix_path'], $filename, 'public');

        return new File(
            path: $path,
            name: $filename,
            extension: $file->getClientOriginalExtension(),
            size: $file->getSize(),
        );
    }

    /**
     * Generate unique filename
     */
    private function generateFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $name = Str::random(40);

        return "{$name}.{$extension}";
    }

    /**
     * Get full URL of the file from path
     */
    public function getUrl(string $path): string
    {
        return Storage::disk('public')->url($path);
    }
}
