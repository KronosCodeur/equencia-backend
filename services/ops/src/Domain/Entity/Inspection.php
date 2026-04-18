<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\InspectionStatus;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'inspections')]
#[ORM\HasLifecycleCallbacks]
class Inspection
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(type: 'uuid')]
    private Uuid $inspectorId;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $site;

    #[ORM\Column(type: 'string', enumType: InspectionStatus::class)]
    private InspectionStatus $status;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $scheduledAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $startedAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $completedAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $findings;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        Uuid $tenantId,
        Uuid $inspectorId,
        DateTimeImmutable $scheduledAt,
        ?string $site,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->inspectorId = $inspectorId;
        $this->scheduledAt = $scheduledAt;
        $this->site = $site;
        $this->status = InspectionStatus::Scheduled;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function schedule(
        Uuid $tenantId,
        Uuid $inspectorId,
        DateTimeImmutable $scheduledAt,
        ?string $site = null,
    ): self {
        return new self(Uuid::v7(), $tenantId, $inspectorId, $scheduledAt, $site);
    }

    public function start(): void
    {
        $this->status = InspectionStatus::InProgress;
        $this->startedAt = new DateTimeImmutable();
        $this->touch();
    }

    public function complete(string $notes, array $findings): void
    {
        $this->status = InspectionStatus::Completed;
        $this->completedAt = new DateTimeImmutable();
        $this->notes = $notes;
        $this->findings = $findings;
        $this->touch();
    }

    public function cancel(): void
    {
        $this->status = InspectionStatus::Cancelled;
        $this->touch();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenantId(): Uuid { return $this->tenantId; }
    public function getInspectorId(): Uuid { return $this->inspectorId; }
    public function getSite(): ?string { return $this->site; }
    public function getStatus(): InspectionStatus { return $this->status; }
    public function getScheduledAt(): DateTimeImmutable { return $this->scheduledAt; }
    public function getStartedAt(): ?DateTimeImmutable { return $this->startedAt; }
    public function getCompletedAt(): ?DateTimeImmutable { return $this->completedAt; }
    public function getNotes(): ?string { return $this->notes; }
    public function getFindings(): ?array { return $this->findings; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
