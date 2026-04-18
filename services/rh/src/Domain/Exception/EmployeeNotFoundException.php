<?php

declare(strict_types=1);

namespace App\Domain\Exception;

use DomainException;

final class EmployeeNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self(sprintf('Employee "%s" not found.', $id));
    }
}
