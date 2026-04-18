<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum LeaveType: string
{
    case Paid       = 'paid';
    case Sick       = 'sick';
    case Maternity  = 'maternity';
    case Paternity  = 'paternity';
    case Unpaid     = 'unpaid';
    case Other      = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Paid      => 'Congé payé',
            self::Sick      => 'Congé maladie',
            self::Maternity => 'Congé maternité',
            self::Paternity => 'Congé paternité',
            self::Unpaid    => 'Congé sans solde',
            self::Other     => 'Autre',
        };
    }
}
