<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum DocumentStatus: string
{
    case Pending = 'pending';
    case Ready   = 'ready';
    case Failed  = 'failed';
}
