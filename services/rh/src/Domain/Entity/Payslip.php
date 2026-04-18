<?php

declare(strict_types=1);

namespace App\Domain\Entity;

use App\Domain\Entity\Enum\PayslipStatus;
use App\Domain\Exception\InvalidPayslipException;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table(name: 'payslips')]
#[ORM\UniqueConstraint(columns: ['employee_id', 'period_year', 'period_month'], name: 'uq_payslip_employee_period')]
#[ORM\HasLifecycleCallbacks]
class Payslip
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(type: 'uuid')]
    private Uuid $tenantId;

    #[ORM\Column(type: 'uuid')]
    private Uuid $employeeId;

    #[ORM\Column(type: 'smallint')]
    private int $periodYear;

    #[ORM\Column(type: 'smallint')]
    private int $periodMonth;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $baseSalaryFcfa;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $grossSalaryFcfa;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $deductionsFcfa;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 2)]
    private string $netSalaryFcfa;

    #[ORM\Column(type: 'integer')]
    private int $workedDays;

    #[ORM\Column(type: 'integer')]
    private int $absenceDays;

    #[ORM\Column(type: 'string', enumType: PayslipStatus::class)]
    private PayslipStatus $status;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $pdfStoragePath;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $paidAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updatedAt;

    private function __construct(
        Uuid $id,
        Uuid $tenantId,
        Uuid $employeeId,
        int $periodYear,
        int $periodMonth,
        string $baseSalaryFcfa,
        string $grossSalaryFcfa,
        string $deductionsFcfa,
        string $netSalaryFcfa,
        int $workedDays,
        int $absenceDays,
    ) {
        $this->id = $id;
        $this->tenantId = $tenantId;
        $this->employeeId = $employeeId;
        $this->periodYear = $periodYear;
        $this->periodMonth = $periodMonth;
        $this->baseSalaryFcfa = $baseSalaryFcfa;
        $this->grossSalaryFcfa = $grossSalaryFcfa;
        $this->deductionsFcfa = $deductionsFcfa;
        $this->netSalaryFcfa = $netSalaryFcfa;
        $this->workedDays = $workedDays;
        $this->absenceDays = $absenceDays;
        $this->status = PayslipStatus::Draft;
        $this->createdAt = new DateTimeImmutable();
        $this->updatedAt = new DateTimeImmutable();
    }

    public static function generate(
        Uuid $tenantId,
        Uuid $employeeId,
        int $periodYear,
        int $periodMonth,
        string $baseSalaryFcfa,
        string $grossSalaryFcfa,
        string $deductionsFcfa,
        string $netSalaryFcfa,
        int $workedDays,
        int $absenceDays,
    ): self {
        if ($periodMonth < 1 || $periodMonth > 12) {
            throw new InvalidPayslipException('Invalid period month.');
        }

        return new self(
            Uuid::v7(),
            $tenantId,
            $employeeId,
            $periodYear,
            $periodMonth,
            $baseSalaryFcfa,
            $grossSalaryFcfa,
            $deductionsFcfa,
            $netSalaryFcfa,
            $workedDays,
            $absenceDays,
        );
    }

    public function attachPdf(string $storagePath): void
    {
        $this->pdfStoragePath = $storagePath;
        $this->touch();
    }

    public function markAsPaid(): void
    {
        $this->status = PayslipStatus::Paid;
        $this->paidAt = new DateTimeImmutable();
        $this->touch();
    }

    public function validate(): void
    {
        $this->status = PayslipStatus::Validated;
        $this->touch();
    }

    public function getId(): Uuid { return $this->id; }
    public function getTenantId(): Uuid { return $this->tenantId; }
    public function getEmployeeId(): Uuid { return $this->employeeId; }
    public function getPeriodYear(): int { return $this->periodYear; }
    public function getPeriodMonth(): int { return $this->periodMonth; }
    public function getBaseSalaryFcfa(): string { return $this->baseSalaryFcfa; }
    public function getGrossSalaryFcfa(): string { return $this->grossSalaryFcfa; }
    public function getDeductionsFcfa(): string { return $this->deductionsFcfa; }
    public function getNetSalaryFcfa(): string { return $this->netSalaryFcfa; }
    public function getWorkedDays(): int { return $this->workedDays; }
    public function getAbsenceDays(): int { return $this->absenceDays; }
    public function getStatus(): PayslipStatus { return $this->status; }
    public function getPdfStoragePath(): ?string { return $this->pdfStoragePath; }
    public function getPaidAt(): ?DateTimeImmutable { return $this->paidAt; }
    public function getCreatedAt(): DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): DateTimeImmutable { return $this->updatedAt; }

    #[ORM\PreUpdate]
    public function touch(): void
    {
        $this->updatedAt = new DateTimeImmutable();
    }
}
