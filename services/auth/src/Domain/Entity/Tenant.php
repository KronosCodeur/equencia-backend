<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Equencia\Shared\ValueObject\TenantId;

#[ORM\Entity]
#[ORM\Table(name: 'tenants')]
final class Tenant
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    private string $id;

    #[ORM\Column(length: 100)]
    private string $name;

    #[ORM\Column(length: 50, unique: true)]
    private string $slug;

    #[ORM\Column(enumType: TenantPlan::class)]
    private TenantPlan $plan;

    #[ORM\Column(enumType: TenantStatus::class)]
    private TenantStatus $status;

    #[ORM\Column(type: 'integer')]
    private int $employeeLimit;

    #[ORM\Column(type: 'datetimetz_immutable', nullable: true)]
    private ?\DateTimeImmutable $trialEndsAt;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetimetz_immutable')]
    private \DateTimeImmutable $updatedAt;

    private function __construct(
        TenantId $id,
        string $name,
        string $slug,
        TenantPlan $plan,
    ) {
        $this->id            = $id->value;
        $this->name          = $name;
        $this->slug          = $slug;
        $this->plan          = $plan;
        $this->status        = TenantStatus::Trial;
        $this->employeeLimit = $plan->employeeLimit();
        $this->trialEndsAt   = new \DateTimeImmutable('+14 days');
        $this->createdAt     = new \DateTimeImmutable();
        $this->updatedAt     = new \DateTimeImmutable();
    }

    public static function create(string $name, string $slug, TenantPlan $plan = TenantPlan::Starter): self
    {
        return new self(TenantId::generate(), $name, $slug, $plan);
    }

    public function activate(): void
    {
        $this->status      = TenantStatus::Active;
        $this->trialEndsAt = null;
        $this->updatedAt   = new \DateTimeImmutable();
    }

    public function suspend(): void
    {
        $this->status    = TenantStatus::Suspended;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function upgradePlan(TenantPlan $plan): void
    {
        $this->plan          = $plan;
        $this->employeeLimit = $plan->employeeLimit();
        $this->updatedAt     = new \DateTimeImmutable();
    }

    public function isTrialExpired(): bool
    {
        return $this->trialEndsAt !== null && $this->trialEndsAt < new \DateTimeImmutable();
    }

    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active;
    }

    // ── Getters ─────────────────────────────────────────────
    public function id(): TenantId { return TenantId::from($this->id); }
    public function name(): string { return $this->name; }
    public function slug(): string { return $this->slug; }
    public function plan(): TenantPlan { return $this->plan; }
    public function status(): TenantStatus { return $this->status; }
    public function employeeLimit(): int { return $this->employeeLimit; }
    public function trialEndsAt(): ?\DateTimeImmutable { return $this->trialEndsAt; }
}
