<?php

namespace App\Share\Services\Setting\EVisa;

class LengthOfVisaOption
{
    public function __construct(
        public float $price,
        public ?float $adminAndGovernmentFee = null,
    ) {}

    public function toArray(): array
    {
        return [
            'price' => $this->price,
            'admin_and_government_fee' => $this->adminAndGovernmentFee,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            price: (float) $data['price'],
            adminAndGovernmentFee: isset($data['admin_and_government_fee']) ? (float) $data['admin_and_government_fee'] : null,
        );
    }
}
