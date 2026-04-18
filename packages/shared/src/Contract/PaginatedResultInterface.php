<?php

declare(strict_types=1);

namespace Equencia\Shared\Contract;

interface PaginatedResultInterface
{
    public function items(): array;

    public function total(): int;

    public function page(): int;

    public function perPage(): int;

    public function totalPages(): int;

    public function hasNextPage(): bool;
}
