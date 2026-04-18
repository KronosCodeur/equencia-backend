<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Equencia\Shared\ValueObject\TenantId;
use Equencia\Shared\ValueObject\UserId;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity]
#[ORM\Table(name: 'users')]
#[ORM\Index(columns: ['tenant_id'], name: 'idx_users_tenant')]
#[ORM\Index(columns: ['tenant_id', 'role'], name: 'idx_users_tenant_role')]
final class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(type: 'uuid', nullable: false)]
    private string $tenantId;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column]
    private string $passwordHash;

    #[ORM\Column(enumType: UserRole::class)]
    private UserRole $role;

    #[ORM\Column(length: 50)]
    private string $firstName;

    #[ORM\Column(length: 50)]
    private string $lastName;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $phoneWhatsapp;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $lastLoginAt;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        UserId $id,
        TenantId $tenantId,
        string $email,
        string $passwordHash,
        UserRole $role,
        string $firstName,
        string $lastName,
        ?string $phoneWhatsapp,
    ) {
        $this->id            = $id->value;
        $this->tenantId      = $tenantId->value;
        $this->email         = $email;
        $this->passwordHash  = $passwordHash;
        $this->role          = $role;
        $this->firstName     = $firstName;
        $this->lastName      = $lastName;
        $this->phoneWhatsapp = $phoneWhatsapp;
        $this->createdAt     = new \DateTimeImmutable();
        $this->updatedAt     = new \DateTimeImmutable();
    }

    public static function create(
        TenantId $tenantId,
        string $email,
        string $passwordHash,
        UserRole $role,
        string $firstName,
        string $lastName,
        ?string $phoneWhatsapp = null,
    ): self {
        return new self(
            id: UserId::generate(),
            tenantId: $tenantId,
            email: $email,
            passwordHash: $passwordHash,
            role: $role,
            firstName: $firstName,
            lastName: $lastName,
            phoneWhatsapp: $phoneWhatsapp,
        );
    }

    public function recordLogin(): void
    {
        $this->lastLoginAt = new \DateTimeImmutable();
        $this->updatedAt   = new \DateTimeImmutable();
    }

    public function deactivate(): void
    {
        $this->isActive  = false;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function changeRole(UserRole $role): void
    {
        $this->role      = $role;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    // ── UserInterface ───────────────────────────────────────
    public function getUserIdentifier(): string { return $this->email; }
    public function getRoles(): array { return [$this->role->toSymfonyRole()]; }
    public function getPassword(): string { return $this->passwordHash; }
    public function eraseCredentials(): void {}

    // ── Getters ─────────────────────────────────────────────
    public function id(): UserId { return UserId::from($this->id); }
    public function tenantId(): TenantId { return TenantId::from($this->tenantId); }
    public function email(): string { return $this->email; }
    public function role(): UserRole { return $this->role; }
    public function firstName(): string { return $this->firstName; }
    public function lastName(): string { return $this->lastName; }
    public function phoneWhatsapp(): ?string { return $this->phoneWhatsapp; }
    public function isActive(): bool { return $this->isActive; }
    public function lastLoginAt(): ?\DateTimeImmutable { return $this->lastLoginAt; }
    public function createdAt(): \DateTimeImmutable { return $this->createdAt; }
}
