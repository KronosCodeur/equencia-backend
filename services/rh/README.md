# RH Service

**Responsabilités :** Employés · Présences · Planning · Congés · Paie  
**Port :** 8002  
**Préfixes API :** `/api/employees` · `/api/attendance` · `/api/shifts` · `/api/leaves` · `/api/payslips`

## Endpoints

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| GET | `/api/employees` | JWT | Lister les employés du tenant |
| POST | `/api/employees` | JWT + Manager | Créer un employé |
| GET | `/api/employees/{id}` | JWT | Détail employé |
| PATCH | `/api/employees/{id}` | JWT + Manager | Modifier profil |
| DELETE | `/api/employees/{id}` | JWT + Admin | Désactiver |
| GET | `/api/employees/{id}/qr` | JWT + Manager | Générer/afficher badge QR |
| POST | `/api/attendance/check-in` | JWT | Pointer l'arrivée |
| POST | `/api/attendance/check-out` | JWT | Pointer le départ |
| GET | `/api/attendance` | JWT | Historique présences |
| GET | `/api/shifts` | JWT | Planning (semaine/mois) |
| POST | `/api/shifts` | JWT + Manager | Créer un shift |
| PUT | `/api/shifts/{id}` | JWT + Manager | Modifier un shift |
| POST | `/api/shifts/{id}/publish` | JWT + Manager | Publier le planning |
| GET | `/api/leaves` | JWT | Mes demandes de congé |
| POST | `/api/leaves` | JWT | Soumettre une demande |
| PATCH | `/api/leaves/{id}/approve` | JWT + Manager | Approuver |
| PATCH | `/api/leaves/{id}/reject` | JWT + Manager | Refuser |
| GET | `/api/payslips` | JWT | Mes bulletins de paie |
| POST | `/api/payslips/generate` | JWT + HR | Générer les bulletins |
| GET | `/api/payslips/{id}/pdf` | JWT | Télécharger le bulletin |

## Variables d'environnement requises

Voir `.env.example`.

## Commandes utiles

```bash
make sh-rh                    # Ouvrir un shell dans le container
make migrate-rh               # Exécuter les migrations
make test-rh                  # Lancer les tests
make migration-diff-rh        # Générer une migration depuis les entités
```

## Architecture

```
src/
├── Domain/
│   ├── Entity/
│   │   ├── Employee.php        Employé avec QR code hash
│   │   ├── Attendance.php      Pointage (check-in/check-out)
│   │   ├── Shift.php           Créneau de planning
│   │   ├── LeaveRequest.php    Demande de congé
│   │   ├── Payslip.php         Bulletin de paie
│   │   └── Enum/               ContractType, EmployeeStatus, AttendanceSource…
│   ├── Repository/             Interfaces (ports)
│   └── Exception/              Exceptions métier typées
├── Application/
│   ├── Command/                CreateEmployee/, RecordAttendance/, SubmitLeave/…
│   ├── Query/                  GetEmployeeById/, ListAttendance/, GetPayslip/…
│   └── DTO/                    CreateEmployeeRequest, CheckInRequest…
├── Infrastructure/
│   ├── Persistence/            DoctrineEmployeeRepository, etc.
│   ├── Security/               JwtUser, JwtUserProvider, TenantContext
│   ├── EventListener/          TenantMiddleware
│   ├── External/               GotenbergClient (PDF), MinioClient (storage)
│   └── Messenger/              Messages async inter-services
└── Interface/
    ├── Controller/             HealthController
    └── Api/                    API Platform Resources
```

## Messages async émis

| Message | Transport | Déclencheur |
|---------|-----------|-------------|
| `EmployeeCreatedMessage` | Redis | Après création employé → notification-service |
| `PayslipGeneratedMessage` | Redis | Après génération → document-service (PDF) |
| `LeaveApprovedMessage` | Redis | Après validation congé → notification-service |

## Messages async consommés

| Message | Source | Action |
|---------|--------|--------|
| `TenantRegisteredMessage` | auth-service | Initialisation schema tenant |

## Entités Domain

| Entité | Table | Description |
|--------|-------|-------------|
| `Employee` | `employees` | Profil employé + QR hash + statut |
| `Attendance` | `attendances` | Pointage avec source (QR, NFC, GPS, manuel) |
| `Shift` | `shifts` | Créneau planning (publié ou brouillon) |
| `LeaveRequest` | `leave_requests` | Demande congé avec workflow (pending → approved/rejected) |
| `Payslip` | `payslips` | Bulletin de paie mensuel (draft → validated → paid) |
