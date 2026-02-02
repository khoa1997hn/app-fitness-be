<?php

namespace App\Share\Services\File;

class FileConfigService
{
    /**
     * Lấy config cho file type
     *
     * @return array{prefix_path?: string, allow_mimetypes?: array<string>, allow_max_size?: int}
     */
    public function getConfig(string $type): array
    {
        return config("app_file.{$type}") ?? [];
    }
}
