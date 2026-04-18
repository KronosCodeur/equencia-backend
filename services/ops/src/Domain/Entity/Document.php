<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\DocumentStatus;
use App\Domain\Entity\Enum\DocumentType;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'documents')]
#[ORM\HasLifecycleCallbacks]
class Document
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?Uuid $referenceId;

    #[ORM\Column(type: 'string', enumType: DocumentType::class)]
    private DocumentType $type;

    #[ORM\Column(type: 'string', enumType: DocumentStatus::class)]
    private DocumentStatus $status;

    #[ORM\Column(length: 255)]
    private string $filename;

    #[ORM\Column(length: 512, nullable: true)]
    private ?string $storagePath;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $sizeBytes;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $generatedAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        Uuid $tenantId,
        DocumentType $type,
        string $filename,
        ?Uuid $referenceId,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->type = $type;
        $this->filename = $filename;
        $this->referenceId = $referenceId;
        $this->status = DocumentStatus::Pending;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function request(
        Uuid $tenantId,
        DocumentType $type,
        string $filename,
        ?Uuid $referenceId = null,
    ): self {
        return new self(Uuid::v7(), $tenantId, $type, $filename, $referenceId);
    }

    public function markAsGenerated(string $storagePath, int $sizeBytes): void
    {
        $this->status = DocumentStatus::Ready;
        $this->storagePath = $storagePath;
        $this->sizeBytes = $sizeBytes;
        $this->generatedAt = new DateTimeImmutable();
        $this->touch();
    }

    public function markAsFailed(): void
    {
        $this->status = DocumentStatus::Failed;
        $this->touch();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenantId(): Uuid { return $this->tenantId; }
    public function getReferenceId(): ?Uuid { return $this->referenceId; }
    public function getType(): DocumentType { return $this->type; }
    public function getStatus(): DocumentStatus { return $this->status; }
    public function getFilename(): string { return $this->filename; }
    public function getStoragePath(): ?string { return $this->storagePath; }
    public function getSizeBytes(): ?int { return $this->sizeBytes; }
    public function getGeneratedAt(): ?DateTimeImmutable { return $this->generatedAt; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }
    public function isReady(): bool { return $this->status === DocumentStatus::Ready; }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
