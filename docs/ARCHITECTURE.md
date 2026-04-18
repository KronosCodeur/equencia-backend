# Architecture Equencia Backend

## Vue d'ensemble

```
                    ┌─────────────────────────────────┐
  Browser/Mobile    │         Nginx (local)            │
  ──────────────▶   │         Traefik (prod)           │
                    └──────┬──────────┬────────────────┘
                           │          │          │
                    ┌──────▼──┐ ┌─────▼──┐ ┌────▼────┐
                    │  auth   │ │   rh   │ │   ops   │
                    │ :8001   │ │ :8002  │ │  :8003  │
                    └──────┬──┘ └─────┬──┘ └────┬────┘
                           │          │          │
                    ┌──────▼──────────▼──────────▼────┐
                    │         PostgreSQL 16            │
                    │    (shared DB + tenant_id + RLS) │
                    └─────────────────────────────────┘
                           │          │          │
                    ┌──────▼──┐ ┌─────▼────────────────┐
                    │  Redis  │ │      Mercure SSE       │
                    │  :6379  │ │      (temps réel)      │
                    └─────────┘ └──────────────────────┘
```

## Services

### auth (port 8001)
Point d'entrée d'authentification. Émet les JWT RS256. Seul service avec la clé privée.

**Responsabilités :** Inscription tenant, login, refresh token, reset password, profil utilisateur, informations tenant.

### rh (port 8002)
Cœur métier RH. Le service le plus large — couvre 5 domaines.

**Responsabilités :** Employés + badges QR, pointage présences, planning shifts, congés, paie.

### ops (port 8003)
Services opérationnels transverses.

**Responsabilités :** Inspections inopinées, génération PDF (via Gotenberg), notifications multi-canal (email, push, WhatsApp).

## Patterns d'architecture

### Hexagonale + CQRS dans chaque service

```
Domain/          ← Zéro dépendance framework
  Entity/        ← Entités riches (logique métier + domain events)
  Repository/    ← Interfaces (ports)
  Exception/     ← Exceptions métier typées

Application/     ← Orchestration use cases
  Command/       ← [Action]Command + [Action]Handler
  Query/         ← [Action]Query + [Action]Handler

Infrastructure/  ← Adaptateurs concrets
  Persistence/   ← Doctrine repositories (implémentent Domain/Repository/)
  Security/      ← JwtUser, TenantContext
  EventListener/ ← TenantMiddleware
  External/      ← Clients HTTP tiers

Interface/       ← Points d'entrée
  Api/           ← API Platform Resources
  Controller/    ← Controllers custom
```

### Multi-tenancy à 3 couches

```
Requête HTTP
    ↓
TenantMiddleware      ← couche 1 : positionne TenantContext depuis JWT
    ↓
Repository            ← couche 2 : WHERE tenant_id = :tenantId
    ↓
PostgreSQL RLS        ← couche 3 : policy tenant_isolation
    ↓
Données du tenant
```

### Communication inter-services

```
rh-service ──Redis Messenger──▶ ops-service
              (PayslipGeneratedMessage)
                  ↓
              GotenbergClient → PDF généré
              MinioClient → upload stockage
              DocumentReadyMessage → Redis
```

Les services ne s'appellent **jamais** directement en HTTP interne. Seules les communications passent par Redis (async) ou par le frontend (via JWT).

## Flux d'authentification

```
1. POST /api/auth/login
   → auth-service valide les credentials
   → émet JWT RS256 (TTL 15min) + refresh cookie (TTL 7j)

2. GET /api/employees  (avec Authorization: Bearer <jwt>)
   → nginx route vers rh-service
   → LexikJWT valide la signature avec public.pem
   → TenantMiddleware extrait tenant_id du payload
   → SET app.current_tenant_id = <tenant_id>
   → RLS active sur toutes les requêtes Doctrine
```

## Scalabilité future

L'architecture permet d'extraire des bounded contexts sans réécriture :

- `rh/` → séparer `planning-service` si le planning devient très complexe
- `ops/` → séparer `notification-service` si le volume de notifications explose
- Chaque extraction = copier le dossier Domain + adapter la config Symfony

## Infrastructure locale vs production

| Composant | Local | Production |
|-----------|-------|-----------|
| Gateway | Nginx | Traefik v3 |
| TLS | Non | Let's Encrypt |
| Orchestration | Docker Compose | Docker Compose (single server) |
| Secrets | `.env` | Variables d'environnement sécurisées |
| Logs | stdout | Loki / Grafana |
| Emails | MailHog | SMTP réel |
