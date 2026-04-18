<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum NotificationChannel: string
{
    case Email    = 'email';
    case Push     = 'push';
    case WhatsApp = 'whatsapp';
    case Sms      = 'sms';
}
