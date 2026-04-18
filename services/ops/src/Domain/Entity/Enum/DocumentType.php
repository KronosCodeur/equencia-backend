<?php

declare(strict_types=1);

namespace App\Domain\Entity\Enum;

enum DocumentType: string
{
    case Payslip          = 'payslip';
    case AttendanceReport = 'attendance_report';
    case InspectionReport = 'inspection_report';
    case Contract         = 'contract';
    case LeaveAttestation = 'leave_attestation';
}
