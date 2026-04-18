# Ops Service

**Responsabilités :** Inspections · Documents (PDF) · Notifications  
**Port :** 8003  
**Préfixes API :** `/api/inspections` · `/api/documents` · `/api/notifications`

## Endpoints

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| GET | `/api/inspections` | JWT | Lister les inspections du tenant |
| POST | `/api/inspections` | JWT + Manager | Planifier une inspection |
| PATCH | `/api/inspections/{id}/start` | JWT | Démarrer une inspection |
| PATCH | `/api/inspections/{id}/complete` | JWT | Compléter avec notes et findings |
| GET | `/api/documents` | JWT | Lister les documents générés |
| GET | `/api/documents/{id}/download` | JWT | Télécharger un document |
| GET | `/api/notifications` | JWT | Historique des notifications |

## Variables d'environnement requises

Voir `.env.example`.

## Commandes utiles

```bash
make sh-ops                   # Ouvrir un shell dans le container
make migrate-ops              # Exécuter les migrations
make test-ops                 # Lancer les tests
make migration-diff-ops       # Générer une migration depuis les entités
```

## Architecture

```
src/
├── Domain/
│   ├── Entity/
│   │   ├── Inspection.php      Contrôle inopinée avec findings JSON
│   │   ├── Notification.php    Notification multi-canal (email, push, WhatsApp)
│   │   ├── Document.php        Document généré (PDF) avec stockage MinIO
│   │   └── Enum/               InspectionStatus, NotificationChannel, DocumentType…
│   └── Repository/             Interfaces (ports)
├── Application/
│   ├── Command/                ScheduleInspection/, SendNotification/, GenerateDocument/
│   └── Query/                  GetInspection/, ListDocuments/
├── Infrastructure/
│   ├── Persistence/            DoctrineInspectionRepository, etc.
│   ├── Security/               JwtUser, JwtUserProvider, TenantContext
│   ├── EventListener/          TenantMiddleware
│   ├── External/
│   │   ├── GotenbergClient.php Génération PDF via Gotenberg
│   │   ├── MinioClient.php     Upload/download S3 MinIO
│   │   ├── FcmClient.php       Push notifications Firebase
│   │   └── WhatsAppClient.php  Notifications WhatsApp
│   └── Messenger/              Consumers (PayslipGeneratedMessage → PDF)
└── Interface/
    ├── Controller/             HealthController
    └── Api/                    API Platform Resources
```

## Messages async consommés

| Message | Source | Action |
|---------|--------|--------|
| `PayslipGeneratedMessage` | rh-service | Générer PDF + uploader MinIO + notifier |
| `EmployeeCreatedMessage` | rh-service | Envoyer email de bienvenue |
| `LeaveApprovedMessage` | rh-service | Notifier l'employé |

## Messages async émis

| Message | Transport | Déclencheur |
|---------|-----------|-------------|
| `DocumentReadyMessage` | Redis | PDF prêt → rh-service pour MAJ du payslip |
