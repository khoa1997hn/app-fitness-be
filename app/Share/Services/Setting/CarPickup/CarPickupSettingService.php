<?php

namespace App\Share\Services\Setting\CarPickup;

use App\Share\Enums\Service;
use App\Share\Models\Setting;
use App\Share\Services\Setting\SettingService;

class CarPickupSettingService extends SettingService
{
    /**
     * Get car type
     */
    public function getCarType(): ?string
    {
        return $this->get(Service::CarPickup.'.car_type');
    }

    /**
     * Set car type
     */
    public function setCarType(CarPickupCarType $carType): Setting
    {
        return $this->set(Service::CarPickup.'.car_type', $carType->toJson());
    }

    /**
     * Delete car type
     */
    public function deleteCarType(): bool
    {
        return $this->delete(Service::CarPickup.'.car_type');
    }
}
