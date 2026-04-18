<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum AttendanceSource: string
{
    case QrCode  = 'qr_code';
    case Nfc     = 'nfc';
    case Manual  = 'manual';
    case Gps     = 'gps';
}
