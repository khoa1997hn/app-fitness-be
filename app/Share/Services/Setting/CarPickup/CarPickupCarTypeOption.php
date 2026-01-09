<?php

namespace App\Share\Services\Setting\CarPickup;

class CarPickupCarTypeOption
{
    public function __construct(
        public float $price,
    ) {}

    public function toArray(): array
    {
        return [
            'price' => $this->price,
        ];
    }

    public static function fromArray(array $data): self
    {
        return new self(
            price: (float) $data['price']
        );
    }
}
