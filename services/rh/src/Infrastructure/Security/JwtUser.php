<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Uid\Uuid;

final class JwtUser implements UserInterface
{
    public function __construct(
        private readonly string $identifier,
        private readonly array $roles,
        private readonly ?string $tenantId = null,
        private readonly ?string $tenantSlug = null,
        private readonly ?string $role = null,
    ) {}

    public function getUserIdentifier(): string
    {
        return $this->identifier;
    }

    public function getRoles(): array
    {
        return array_unique([...$this->roles, 'ROLE_USER']);
    }

    public function eraseCredentials(): void {}

    public function getTenantId(): ?Uuid
    {
        return $this->tenantId !== null ? Uuid::fromString($this->tenantId) : null;
    }

    public function getTenantSlug(): ?string
    {
        return $this->tenantSlug;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }
}
