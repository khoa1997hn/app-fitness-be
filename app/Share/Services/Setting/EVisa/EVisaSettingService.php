<?php

namespace App\Share\Services\Setting\EVisa;

use App\Share\Enums\Service;
use App\Share\Models\Setting;
use App\Share\Services\Setting\SettingService;

class EVisaSettingService extends SettingService
{
    /**
     * Get length of visa
     */
    public function getLengthOfVisa(): ?string
    {
        return $this->get(Service::EVisa.'.length_of_visa');
    }

    /**
     * Set length of visa
     */
    public function setLengthOfVisa(LengthOfVisa $lengthOfVisa): Setting
    {
        return $this->set(Service::EVisa.'.length_of_visa', $lengthOfVisa->toJson());
    }

    /**
     * Delete length of visa
     */
    public function deleteLengthOfVisa(): bool
    {
        return $this->delete(Service::EVisa.'.length_of_visa');
    }

    /**
     * Get processing time
     */
    public function getProcessingTime(): ?string
    {
        return $this->get(Service::EVisa.'.processing_time');
    }

    /**
     * Set processing time
     */
    public function setProcessingTime(ProcessingTime $processingTime): Setting
    {
        return $this->set(Service::EVisa.'.processing_time', $processingTime->toJson());
    }

    /**
     * Delete processing time
     */
    public function deleteProcessingTime(): bool
    {
        return $this->delete(Service::EVisa.'.processing_time');
    }
}
