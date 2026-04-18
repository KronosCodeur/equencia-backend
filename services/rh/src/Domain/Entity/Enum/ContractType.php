<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum ContractType: string
{
    case CDI = 'CDI';
    case CDD = 'CDD';
    case Stage = 'stage';
    case Journalier = 'journalier';
    case Freelance = 'freelance';

    public function label(): string
    {
        return match ($this) {
            self::CDI       => 'Contrat à durée indéterminée',
            self::CDD       => 'Contrat à durée déterminée',
            self::Stage     => 'Stage',
            self::Journalier => 'Journalier / Ouvrier terrain',
            self::Freelance  => 'Prestataire freelance',
        };
    }

    public function isTemporary(): bool
    {
        return in_array($this, [self::CDD, self::Stage, self::Journalier], true);
    }
}
