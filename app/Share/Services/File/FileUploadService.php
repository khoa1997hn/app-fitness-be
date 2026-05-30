<?php

namespace App\Share\Services\File;

use App\Share\Attributes\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

readonly class FileUploadService
{
    public function __construct(
        private FileConfigService $fileConfigService
    ) {}

    /**
     * @return array{
     *     upload_url: string,
     *     method: string,
     *     headers: array<string, string>,
     *     expires_in: int,
     *     file: File
     * }
     */
    public function createPresignedUpload(string $type, string $originalFilename, string $mimetype, int $size): array
    {
        $config = $this->fileConfigService->requireUploadConfig($type);
        $extension = strtolower(pathinfo($originalFilename, PATHINFO_EXTENSION));
        $filename = $this->generateFilename($extension);
        $path = $config['prefix_path'].'/'.$filename;
        $expiresMinutes = $this->fileConfigService->presignedExpiresMinutes();
        $expiresAt = now()->addMinutes($expiresMinutes);

        $uploadResult = Storage::disk($config['disk'])->temporaryUploadUrl(
            $path,
            $expiresAt,
            [
                'ContentType' => $mimetype,
                'ContentLength' => $size,
            ],
        );

        $uploadUrl = is_array($uploadResult) ? ($uploadResult['url'] ?? '') : $uploadResult;
        $headers = is_array($uploadResult)
            ? array_merge(['Content-Type' => $mimetype], $uploadResult['headers'] ?? [])
            : ['Content-Type' => $mimetype];

        return [
            'upload_url' => $uploadUrl,
            'method' => 'PUT',
            'headers' => $headers,
            'expires_in' => $expiresMinutes * 60,
            'file' => new File(
                path: $path,
                name: $filename,
                extension: $extension,
                size: $size,
            ),
        ];
    }

    public function getUrl(string $path): string
    {
        $disk = $this->fileConfigService->getDiskForPath($path);
        $expiresMinutes = $this->fileConfigService->presignedExpiresMinutes();

        return Storage::disk($disk)->temporaryUrl(
            $path,
            now()->addMinutes($expiresMinutes),
        );
    }

    private function generateFilename(string $extension): string
    {
        $extension = ltrim(strtolower($extension), '.');

        return Str::random(40).'.'.$extension;
    }
}
