<?php

namespace App\Share\Services\Setting;

use App\Share\Models\Setting;

class SettingService
{
    /**
     * Parse key thành group và key thực tế
     *
     * @return array{group: string, key: string}
     */
    private function parseKey(string $key): array
    {
        $parts = explode('.', $key);

        if (count($parts) > 1) {
            return [
                'group' => $parts[0],
                'key' => implode('.', array_slice($parts, 1)),
            ];
        }

        return [
            'group' => 'default',
            'key' => $parts[0],
        ];
    }

    /**
     * Tạo mới hoặc cập nhật setting
     */
    public function set(string $key, ?string $value = null): Setting
    {
        $parsed = $this->parseKey($key);

        return Setting::query()->updateOrCreate(
            ['key' => $parsed['key']],
            [
                'group' => $parsed['group'],
                'value' => $value,
            ]
        );
    }

    /**
     * Lấy giá trị setting theo key
     */
    public function get(string $key): ?string
    {
        $parsed = $this->parseKey($key);

        return Setting::query()
            ->where('group', $parsed['group'])
            ->where('key', $parsed['key'])
            ->value('value');
    }

    /**
     * Xóa setting theo key
     */
    public function delete(string $key): bool
    {
        $parsed = $this->parseKey($key);

        return Setting::query()
            ->where('key', $parsed['key'])
            ->delete() > 0;
    }
}
