<?php

declare(strict_types=1);

namespace Equencia\Shared\Contract;

use Equencia\Shared\ValueObject\TenantId;

interface TenantContextInterface
{
    public function set(TenantId $tenantId): void;

    public function get(): TenantId;

    public function has(): bool;

    public function reset(): void;
}
