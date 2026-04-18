<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\ContractType;
use App\Domain\Entity\Enum\EmployeeStatus;
use App\Domain\Exception\InvalidEmployeeDataException;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'employees')]
#[ORM\HasLifecycleCallbacks]
class Employee
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100)]
    private string $lastName;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $phone;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $jobTitle;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $department;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $site;

    #[ORM\Column(type: 'string', enumType: ContractType::class)]
    private ContractType $contractType;

    #[ORM\Column(type: 'string', enumType: EmployeeStatus::class)]
    private EmployeeStatus $status;

    #[ORM\Column(type: 'date_immutable')]
    private DateTimeImmutable $hiredAt;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?DateTimeImmutable $leftAt;

    #[ORM\Column(length: 64, unique: true, nullable: true)]
    private ?string $qrCodeHash;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private array $domainEvents = [];

    private function __construct(
        Uuid $id,
        Uuid $tenantId,
        string $firstName,
        string $lastName,
        string $email,
        ContractType $contractType,
        DateTimeImmutable $hiredAt,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->email = $email;
        $this->contractType = $contractType;
        $this->hiredAt = $hiredAt;
        $this->status = EmployeeStatus::Active;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function create(
        Uuid $tenantId,
        string $firstName,
        string $lastName,
        string $email,
        ContractType $contractType,
        DateTimeImmutable $hiredAt,
    ): self {
        if (trim($firstName) === '' || trim($lastName) === '') {
            throw new InvalidEmployeeDataException('First name and last name are required.');
        }

        return new self(
            Uuid::v7(),
            $tenantId,
            trim($firstName),
            trim($lastName),
            strtolower(trim($email)),
            $contractType,
            $hiredAt,
        );
    }

    public function assignQrCode(string $qrCodeHash): void
    {
        $this->qrCodeHash = $qrCodeHash;
        $this->touch();
    }

    public function terminate(DateTimeImmutable $leftAt): void
    {
        $this->status = EmployeeStatus::Terminated;
        $this->leftAt = $leftAt;
        $this->touch();
    }

    public function suspend(): void
    {
        $this->status = EmployeeStatus::Suspended;
        $this->touch();
    }

    public function reactivate(): void
    {
        $this->status = EmployeeStatus::Active;
        $this->touch();
    }

    public function updateProfile(
        string $jobTitle,
        string $department,
        string $site,
        ?string $phone,
    ): void {
        $this->jobTitle = $jobTitle;
        $this->department = $department;
        $this->site = $site;
        $this->phone = $phone;
        $this->touch();
    }

    public function pullDomainEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenantId(): Uuid { return $this->tenantId; }
    public function getFirstName(): string { return $this->firstName; }
    public function getLastName(): string { return $this->lastName; }
    public function getFullName(): string { return $this->firstName . ' ' . $this->lastName; }
    public function getEmail(): string { return $this->email; }
    public function getPhone(): ?string { return $this->phone; }
    public function getJobTitle(): ?string { return $this->jobTitle; }
    public function getDepartment(): ?string { return $this->department; }
    public function getSite(): ?string { return $this->site; }
    public function getContractType(): ContractType { return $this->contractType; }
    public function getStatus(): EmployeeStatus { return $this->status; }
    public function getHiredAt(): DateTimeImmutable { return $this->hiredAt; }
    public function getLeftAt(): ?DateTimeImmutable { return $this->leftAt; }
    public function getQrCodeHash(): ?string { return $this->qrCodeHash; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }
    public function isActive(): bool { return $this->status === EmployeeStatus::Active; }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }

    private function recordEvent(object $event): void
    {
        $this->domainEvents[] = $event;
    }
}
