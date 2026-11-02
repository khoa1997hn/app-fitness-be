<?php

declare(strict_types=1);

namespace App\Share\Enums;

class PackageType extends Enum
{
    public const DropIn = 'drop_in';

    public const ClassPack = 'class_pack';

    public const Unlimited = 'unlimited';

    public const IntroDemo = 'intro_demo';

    public const PrivatePack = 'private_pack';

    public const PrivateGroup = 'private_group';
}
