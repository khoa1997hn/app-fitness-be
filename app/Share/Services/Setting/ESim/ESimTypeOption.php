<?php

namespace App\Share\Services\Setting\ESim;

class ESimTypeOption
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
