<?php

namespace App\Share\Services\Setting\ESim;

use App\Share\Enums\ESimType as ESimTypeEnum;

class ESimType
{
    public function __construct(
        public ESimTypeOption $viettel,
        public ESimTypeOption $mobifone,
        public ESimTypeOption $vinaphone,
    ) {}

    public function toArray(): array
    {
        return [
            ESimTypeEnum::Viettel => $this->viettel->toArray(),
            ESimTypeEnum::Mobifone => $this->mobifone->toArray(),
            ESimTypeEnum::Vinaphone => $this->vinaphone->toArray(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            viettel: ESimTypeOption::fromArray($data[ESimTypeEnum::Viettel]),
            mobifone: ESimTypeOption::fromArray($data[ESimTypeEnum::Mobifone]),
            vinaphone: ESimTypeOption::fromArray($data[ESimTypeEnum::Vinaphone]),
        );
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, true));
    }
}
