# Auth Service

**Responsabilités :** JWT · Tenants · Users · RBAC · Rate Limiting  
**Port :** 8001  
**Préfixes API :** `/api/auth` · `/api/tenants`

## Endpoints

| Méthode | Route | Auth | Description |
|---------|-------|------|-------------|
| POST | `/api/auth/register` | Public | Créer compte entreprise (tenant + admin) |
| POST | `/api/auth/login` | Public | Obtenir JWT + refresh token |
| POST | `/api/auth/refresh` | Cookie | Renouveler le JWT |
| POST | `/api/auth/logout` | JWT | Invalider le refresh token |
| GET | `/api/auth/me` | JWT | Profil utilisateur courant |
| POST | `/api/auth/password/reset` | Public | Demander reset mot de passe |
| POST | `/api/auth/password/confirm` | Token email | Confirmer reset |
| GET | `/api/tenants/me` | JWT | Informations du tenant courant |
| PATCH | `/api/tenants/me` | JWT + Admin | Modifier le tenant |

## JWT Payload

```json
{
  "sub": "user-uuid",
  "tenant_id": "tenant-uuid",
  "tenant_slug": "btp-lome",
  "role": "manager",
  "plan": "business",
  "email": "user@example.com",
  "iat": 1714000000,
  "exp": 1714000900
}
```

## Variables d'environnement requises

Voir `.env.example`.

Générer les clés JWT depuis la racine : `make jwt-keys`

## Commandes utiles

```bash
make sh-auth                        # Ouvrir un shell dans le container
make migrate-auth                   # Exécuter les migrations
make test-auth                      # Lancer les tests
make migration-diff-auth            # Générer une migration depuis les entités
```

## Architecture

```
src/
├── Domain/
│   ├── Entity/         Tenant, User + enums (UserRole, TenantPlan, TenantStatus)
│   ├── Repository/     TenantRepositoryInterface, UserRepositoryInterface
│   └── Exception/      Exceptions métier (TenantNotFoundException, etc.)
├── Application/
│   ├── Command/        RegisterTenant/, LoginUser/, RefreshToken/, ResetPassword/
│   ├── Query/          GetTenantById/, GetUserById/
│   └── DTO/            RegisterRequest, LoginRequest
├── Infrastructure/
│   ├── Persistence/    DoctrineTenantRepository, DoctrineUserRepository
│   └── Security/       TenantContext, TenantMiddleware, JwtUserProvider
└── Interface/
    └── Controller/     AuthController, TenantController
```

## Messages async émis

| Message | Transport | Déclencheur |
|---------|-----------|-------------|
| `TenantRegisteredMessage` | Redis | Après inscription |
| `PasswordResetRequestedMessage` | Redis | Reset mot de passe |
