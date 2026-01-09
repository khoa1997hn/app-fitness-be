<?php

namespace App\Share\Services\Setting\EVisa;

use App\Share\Enums\EVisaProcessingTime;

class ProcessingTime
{
    public function __construct(
        public ProcessingTimeOption $normal,
        public ProcessingTimeOption $urgent,
        public ProcessingTimeOption $superUrgent,
        public ProcessingTimeOption $express,
        public ProcessingTimeOption $emergency,
        public ProcessingTimeOption $weekendOrHoliday,
    ) {}

    public function toArray(): array
    {
        return [
            EVisaProcessingTime::Normal => $this->normal->toArray(),
            EVisaProcessingTime::Urgent => $this->urgent->toArray(),
            EVisaProcessingTime::SuperUrgent => $this->superUrgent->toArray(),
            EVisaProcessingTime::Express => $this->express->toArray(),
            EVisaProcessingTime::Emergency => $this->emergency->toArray(),
            EVisaProcessingTime::WeekendOrHoliday => $this->weekendOrHoliday->toArray(),
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }

    public static function fromArray(array $data): self
    {
        return new self(
            normal: ProcessingTimeOption::fromArray($data[EVisaProcessingTime::Normal]),
            urgent: ProcessingTimeOption::fromArray($data[EVisaProcessingTime::Urgent]),
            superUrgent: ProcessingTimeOption::fromArray($data[EVisaProcessingTime::SuperUrgent]),
            express: ProcessingTimeOption::fromArray($data[EVisaProcessingTime::Express]),
            emergency: ProcessingTimeOption::fromArray($data[EVisaProcessingTime::Emergency]),
            weekendOrHoliday: ProcessingTimeOption::fromArray($data[EVisaProcessingTime::WeekendOrHoliday]),
        );
    }

    public static function fromJson(string $json): self
    {
        return self::fromArray(json_decode($json, true));
    }
}
