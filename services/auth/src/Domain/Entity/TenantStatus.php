<?php

declare(strict_types=1);

namespace App\Domain\Entity;

enum TenantStatus: string
{
    case Trial     = 'trial';
    case Active    = 'active';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';
}
