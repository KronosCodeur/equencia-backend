<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum LeaveStatus: string
{
    case Pending   = 'pending';
    case Approved  = 'approved';
    case Rejected  = 'rejected';
    case Cancelled = 'cancelled';
}
