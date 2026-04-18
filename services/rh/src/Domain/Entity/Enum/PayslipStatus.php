<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum PayslipStatus: string
{
    case Draft     = 'draft';
    case Validated = 'validated';
    case Paid      = 'paid';
}
