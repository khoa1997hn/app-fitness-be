<?php

namespace App\Share\Services\Setting\EVisa;

use App\Share\Enums\EVisaLength;

class LengthOfVisa
{
    public function __construct(
        public LengthOfVisaOption $oneMonthSingle,
        public LengthOfVisaOption $oneMonthMultiple,
        public LengthOfVisaOption $threeMonthSingle,
        public LengthOfVisaOption $threeMonthMultiple,
    ) {}

    public function toArray(): array
    {
        return [
            EVisaLength::OneMonthSingle => $this->oneMonthSingle->toArray(),
            EVisaLength::OneMonthMultiple => $this->oneMonthMultiple->toArray(),
            EVisaLength::ThreeMonthSingle => $this->threeMonthSingle->toArray(),
            EVisaLength::ThreeMonthMultiple => $this->threeMonthMultiple->toArray(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            oneMonthSingle: LengthOfVisaOption::fromArray($data[EVisaLength::OneMonthSingle]),
            oneMonthMultiple: LengthOfVisaOption::fromArray($data[EVisaLength::OneMonthMultiple]),
            threeMonthSingle: LengthOfVisaOption::fromArray($data[EVisaLength::ThreeMonthSingle]),
            threeMonthMultiple: LengthOfVisaOption::fromArray($data[EVisaLength::ThreeMonthMultiple]),
        );
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, true));
    }
}
