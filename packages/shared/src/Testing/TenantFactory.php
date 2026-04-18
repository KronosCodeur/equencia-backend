<?php

declare(strict_types=1);

namespace Equencia\Shared\Testing;

use Equencia\Shared\ValueObject\TenantId;

final class TenantFactory
{
    public static function devTenantId(): TenantId
    {
        return TenantId::from('00000000-0000-0000-0000-000000000001');
    }

    public static function randomTenantId(): TenantId
    {
        return TenantId::generate();
    }
}
