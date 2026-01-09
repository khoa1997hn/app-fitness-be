<?php

namespace App\Share\Services\Setting\ESim;

use App\Share\Enums\Service;
use App\Share\Models\Setting;
use App\Share\Services\Setting\SettingService;

class ESimSettingService extends SettingService
{
    /**
     * Get type
     */
    public function getType(): ?string
    {
        return $this->get(Service::ESim.'.type');
    }

    /**
     * Set type
     */
    public function setType(ESimType $type): Setting
    {
        return $this->set(Service::ESim.'.type', $type->toJson());
    }

    /**
     * Delete type
     */
    public function deleteType(): bool
    {
        return $this->delete(Service::ESim.'.type');
    }
}
