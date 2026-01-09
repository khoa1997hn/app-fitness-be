<?php

namespace App\Share\Services\Setting\CarPickup;

use App\Share\Enums\CarPickupCarType as CarPickupCarTypeEnum;

class CarPickupCarType
{
    public function __construct(
        public CarPickupCarTypeOption $economy4Seater,
        public CarPickupCarTypeOption $economy7Seater,
        public CarPickupCarTypeOption $economy16Seater,
        public CarPickupCarTypeOption $elegant4Seater,
        public CarPickupCarTypeOption $elegant7Seater,
        public CarPickupCarTypeOption $elegant16Seater,
    ) {}

    public function toArray(): array
    {
        return [
            CarPickupCarTypeEnum::Economy4Seater => $this->economy4Seater->toArray(),
            CarPickupCarTypeEnum::Economy7Seater => $this->economy7Seater->toArray(),
            CarPickupCarTypeEnum::Economy16Seater => $this->economy16Seater->toArray(),
            CarPickupCarTypeEnum::Elegant4Seater => $this->elegant4Seater->toArray(),
            CarPickupCarTypeEnum::Elegant7Seater => $this->elegant7Seater->toArray(),
            CarPickupCarTypeEnum::Elegant16Seater => $this->elegant16Seater->toArray(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            economy4Seater: CarPickupCarTypeOption::fromArray($data[CarPickupCarTypeEnum::Economy4Seater]),
            economy7Seater: CarPickupCarTypeOption::fromArray($data[CarPickupCarTypeEnum::Economy7Seater]),
            economy16Seater: CarPickupCarTypeOption::fromArray($data[CarPickupCarTypeEnum::Economy16Seater]),
            elegant4Seater: CarPickupCarTypeOption::fromArray($data[CarPickupCarTypeEnum::Elegant4Seater]),
            elegant7Seater: CarPickupCarTypeOption::fromArray($data[CarPickupCarTypeEnum::Elegant7Seater]),
            elegant16Seater: CarPickupCarTypeOption::fromArray($data[CarPickupCarTypeEnum::Elegant16Seater]),
        );
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, true));
    }
}
