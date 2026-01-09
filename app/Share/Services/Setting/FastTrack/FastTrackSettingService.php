<?php

namespace App\Share\Services\Setting\FastTrack;

use App\Share\Enums\Service;
use App\Share\Models\Setting;
use App\Share\Services\Setting\SettingService;

class FastTrackSettingService extends SettingService
{
    /**
     * Get type
     */
    public function getType(): ?string
    {
        return $this->get(Service::FastTrack.'.type');
    }

    /**
     * Set type
     */
    public function setType(FastTrackType $type): Setting
    {
        return $this->set(Service::FastTrack.'.type', $type->toJson());
    }

    /**
     * Delete type
     */
    public function deleteType(): bool
    {
        return $this->delete(Service::FastTrack.'.type');
    }
}
