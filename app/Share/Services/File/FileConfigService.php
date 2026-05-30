<?php

namespace App\Share\Services\File;

use App\Share\Exceptions\File\InvalidFileInputException;

class FileConfigService
{
    /**
     * @return array{disk?: string, prefix_path?: string, allow_mimetypes?: array<string>, allow_max_size?: int}
     */
    public function getConfig(string $type): array
    {
        return config("app_file.{$type}") ?? [];
    }

    public function getDefaultDisk(): string
    {
        return (string) config('app_file.default_disk', 's3');
    }

    public function getDisk(string $type): string
    {
        return $this->getConfig($type)['disk'] ?? $this->getDefaultDisk();
    }

    public function getDiskForPath(string $path): string
    {
        foreach (config('app_file', []) as $type => $config) {
            if (! is_array($config) || ! isset($config['prefix_path'])) {
                continue;
            }

            $prefix = $config['prefix_path'];

            if ($path === $prefix || str_starts_with($path, $prefix.'/')) {
                return $this->getDisk((string) $type);
            }
        }

        return $this->getDefaultDisk();
    }

    public function presignedExpiresMinutes(): int
    {
        return (int) config('app_file.presigned_expires_minutes', 15);
    }

    /**
     * @return array{prefix_path: string, allow_mimetypes: list<string>, allow_max_size: int, disk: string}
     *
     * @throws InvalidFileInputException
     */
    public function requireUploadConfig(string $type): array
    {
        $config = $this->getConfig($type);

        if (! isset($config['prefix_path'], $config['allow_mimetypes'], $config['allow_max_size'])) {
            throw new InvalidFileInputException("File type '{$type}' is not configured for upload.");
        }

        return [
            'prefix_path' => $config['prefix_path'],
            'allow_mimetypes' => $config['allow_mimetypes'],
            'allow_max_size' => (int) $config['allow_max_size'],
            'disk' => $this->getDisk($type),
        ];
    }
}
