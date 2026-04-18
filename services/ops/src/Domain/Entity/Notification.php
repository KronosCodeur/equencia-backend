<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\NotificationChannel;
use App\Domain\Entity\Enum\NotificationStatus;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'notifications')]
#[ORM\Index(columns: ['recipient_id', 'created_at'], name: 'idx_notif_recipient_date')]
#[ORM\HasLifecycleCallbacks]
class Notification
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(type: 'uuid')]
    private Uuid $recipientId;

    #[ORM\Column(type: 'string', enumType: NotificationChannel::class)]
    private NotificationChannel $channel;

    #[ORM\Column(type: 'string', enumType: NotificationStatus::class)]
    private NotificationStatus $status;

    #[ORM\Column(length: 200)]
    private string $subject;

    #[ORM\Column(type: 'text')]
    private string $body;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $sentAt;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    private function __construct(
        Uuid $id,
        Uuid $tenantId,
        Uuid $recipientId,
        NotificationChannel $channel,
        string $subject,
        string $body,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->recipientId = $recipientId;
        $this->channel = $channel;
        $this->subject = $subject;
        $this->body = $body;
        $this->status = NotificationStatus::Pending;
        $this->createdAt = new DateTimeImmutable();
    }

    public static function create(
        Uuid $tenantId,
        Uuid $recipientId,
        NotificationChannel $channel,
        string $subject,
        string $body,
    ): self {
        return new self(Uuid::v7(), $tenantId, $recipientId, $channel, $subject, $body);
    }

    public function markAsSent(): void
    {
        $this->status = NotificationStatus::Sent;
        $this->sentAt = new DateTimeImmutable();
    }

    public function markAsFailed(string $error): void
    {
        $this->status = NotificationStatus::Failed;
        $this->errorMessage = $error;
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenantId(): Uuid { return $this->tenantId; }
    public function getRecipientId(): Uuid { return $this->recipientId; }
    public function getChannel(): NotificationChannel { return $this->channel; }
    public function getStatus(): NotificationStatus { return $this->status; }
    public function getSubject(): string { return $this->subject; }
    public function getBody(): string { return $this->body; }
    public function getSentAt(): ?DateTimeImmutable { return $this->sentAt; }
    public function getErrorMessage(): ?string { return $this->errorMessage; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
}
