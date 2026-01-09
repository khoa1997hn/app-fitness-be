<?php

namespace App\Share\Services\Setting\FastTrack;

use App\Share\Enums\FastTrackType as FastTrackTypeEnum;

class FastTrackType
{
    public function __construct(
        public FastTrackTypeOption $normal,
        public FastTrackTypeOption $vipB,
    ) {}

    public function toArray(): array
    {
        return [
            FastTrackTypeEnum::Normal => $this->normal->toArray(),
            FastTrackTypeEnum::VipB => $this->vipB->toArray(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            normal: FastTrackTypeOption::fromArray($data[FastTrackTypeEnum::Normal]),
            vipB: FastTrackTypeOption::fromArray($data[FastTrackTypeEnum::VipB]),
        );
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, true));
    }
}
