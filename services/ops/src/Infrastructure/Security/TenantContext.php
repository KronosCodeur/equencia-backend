<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Equencia\Shared\Contract\TenantContextInterface;
use Equencia\Shared\ValueObject\TenantId;
use LogicException;

final class TenantContext implements TenantContextInterface
{
    private ?TenantId $tenantId = null;

    public function set(TenantId $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function get(): TenantId
    {
        if ($this->tenantId === null) {
            throw new LogicException('TenantContext has not been set. Ensure TenantMiddleware runs first.');
        }

        return $this->tenantId;
    }

    public function has(): bool
    {
        return $this->tenantId !== null;
    }

    public function reset(): void
    {
        $this->tenantId = null;
    }
}
