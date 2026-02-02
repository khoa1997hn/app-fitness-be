<?php

namespace App\Share\Attributes;

use App\Share\Services\File\FileUploadService;

class File implements \JsonSerializable
{
    public function __construct(
        public string $path,
        public string $name,
        public ?string $extension = null,
        public ?int $size = null,
    ) {}

    public function url(): string
    {
        $fileUploadService = app(FileUploadService::class);

        return $fileUploadService->getUrl($this->path);
    }

    public static function fromArray(array $data): ?self
    {
        if (! isset($data['path'])) {
            return null;
        }

        return new self(
            path: $data['path'],
            name: $data['name'] ?? 'unknown',
            extension: $data['extension'] ?? null,
            size: $data['size'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'path' => $this->path,
            'name' => $this->name,
            'extension' => $this->extension,
            'size' => $this->size,
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            ...$this->toArray(),
            'url' => $this->url(),
        ];
    }
}
