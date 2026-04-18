<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Equencia\Shared\Contract\TenantContextInterface;
use Equencia\Shared\ValueObject\TenantId;

final class TenantContext implements TenantContextInterface
{
    private ?TenantId $currentTenantId = null;

    public function set(TenantId $tenantId): void
    {
        $this->currentTenantId = $tenantId;
    }

    public function get(): TenantId
    {
        return $this->currentTenantId
            ?? throw new \LogicException('Aucun contexte tenant — TenantMiddleware non exécuté.');
    }

    public function has(): bool
    {
        return $this->currentTenantId !== null;
    }

    public function reset(): void
    {
        $this->currentTenantId = null;
    }
}
