<?php

namespace App\Share\Casts;

use App\Share\Attributes\File;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class FileCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?File
    {
        if (empty($value)) {
            return null;
        }

        $data = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($data)) {
            throw new InvalidArgumentException("The {$key} attribute must be a valid array or JSON string.");
        }

        return File::fromArray($data);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if (empty($value)) {
            return null;
        }

        if (is_array($value)) {
            $value = File::fromArray($value);
        }

        if ($value === null) {
            return null;
        }

        if (! $value instanceof File) {
            throw new InvalidArgumentException("The {$key} attribute must be a File instance or an array.");
        }

        return json_encode($value->toArray());
    }
}
