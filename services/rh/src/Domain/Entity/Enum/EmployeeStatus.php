<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum EmployeeStatus: string
{
    case Active     = 'active';
    case Suspended  = 'suspended';
    case Terminated = 'terminated';
}
