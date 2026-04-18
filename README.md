# Equencia Backend

API REST microservices pour Equencia — SaaS RH multi-tenant (SAP INNOVATIONS SARL, Lomé).

**Stack :** Symfony 8.0 · PHP 8.4 · PostgreSQL 16 · Redis 7 · Mercure · Docker Compose

## Démarrage rapide

```bash
# 1. Configurer les variables d'environnement
cp .env.example .env
cp services/auth/.env.example services/auth/.env
cp services/rh/.env.example   services/rh/.env
cp services/ops/.env.example  services/ops/.env

# 2. Générer les clés JWT RS256
make jwt-keys

# 3. Démarrer tout l'environnement
make setup
```

Voir [docs/ONBOARDING.md](docs/ONBOARDING.md) pour le guide complet (< 30 min).

## Services

| Service | Port | Rôle |
|---------|------|------|
| `auth` | 8001 | JWT · Tenants · Users · RBAC |
| `rh` | 8002 | Employés · Présences · Planning · Congés · Paie |
| `ops` | 8003 | Inspections · Documents PDF · Notifications |

## Structure

```
equencia-backend/
├── services/
│   ├── auth/          → README + Dockerfile + src/ + config/
│   ├── rh/            → README + Dockerfile + src/ + config/
│   └── ops/           → README + Dockerfile + src/ + config/
├── packages/
│   └── shared/        → Package Composer partagé (TenantId, UserId, Contracts)
├── infrastructure/
│   ├── docker-compose.yml
│   ├── nginx/         → Reverse proxy local (routage par préfixe)
│   ├── traefik/       → Gateway production (TLS Let's Encrypt)
│   └── postgres/      → Init SQL (extensions, RLS, tenant seed)
├── docs/
│   ├── ONBOARDING.md
│   ├── ARCHITECTURE.md
│   └── adr/           → ADR-001 à ADR-004
├── Makefile           → Point d'entrée unique
└── .env.example       → Variables globales documentées
```

## Commandes

```bash
make setup            # Première installation complète
make dev              # Démarrer les containers
make stop             # Arrêter
make logs             # Logs de tous les services
make test             # Tests des 3 services
make lint             # PHP-CS-Fixer
make phpstan          # PHPStan level 8
make migrate          # Migrations des 3 services
make db-reset         # DANGER : reset complet DB
```

## Documentation

- [ONBOARDING](docs/ONBOARDING.md) — Guide de démarrage
- [ARCHITECTURE](docs/ARCHITECTURE.md) — Vue d'ensemble technique
- [ADR-001](docs/adr/ADR-001-trois-services.md) — Pourquoi 3 services
- [ADR-002](docs/adr/ADR-002-shared-db-rls.md) — Shared DB + RLS
- [ADR-003](docs/adr/ADR-003-jwt-rs256.md) — JWT RS256
- [ADR-004](docs/adr/ADR-004-mercure-sse.md) — Mercure SSE

## Multi-tenancy

Chaque tenant est isolé via **Row Level Security PostgreSQL**. Le JWT contient `tenant_id`. `TenantMiddleware` positionne `SET app.current_tenant_id` sur la connexion Doctrine à chaque requête — activant automatiquement les policies RLS sur toutes les tables.

## Communication inter-services

Les services communiquent uniquement via **Redis Streams** (Symfony Messenger) — jamais d'appel HTTP direct entre services. Les events temps réel passent par **Mercure SSE**.
