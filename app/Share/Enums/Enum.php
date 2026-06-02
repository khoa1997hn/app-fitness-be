<?php

declare(strict_types=1);

namespace App\Share\Enums;

use BenSampo\Enum\Contracts\LocalizedEnum;
use BenSampo\Enum\Enum as BenSampoEnum;

abstract class Enum extends BenSampoEnum implements LocalizedEnum
{
    // Label hiển thị lấy từ lang/<locale>/enums.php (key: enums.<FQCN>.<value>).
    // Thiếu key → tự fallback friendly name của BenSampo.
}
