<?php

declare(strict_types=1);

namespace App\Share\Enums;

class Role extends Enum
{
    public const Admin = 'admin';

    public const Receptionist = 'receptionist';

    public const Trainer = 'trainer';

    public const Member = 'member';
}
