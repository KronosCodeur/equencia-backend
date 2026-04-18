# Onboarding — Démarrer en moins de 30 minutes

## Prérequis

- Docker Desktop ≥ 4.x + Docker Compose v2
- Make
- Git

## 1. Cloner et configurer l'environnement (5 min)

```bash
git clone <repo-url> equencia
cd equencia/equencia-backend

# Copier les variables d'environnement
cp .env.example .env

# Copier les .env de chaque service
cp services/auth/.env.example services/auth/.env
cp services/rh/.env.example   services/rh/.env
cp services/ops/.env.example  services/ops/.env
```

## 2. Générer les clés JWT (2 min)

```bash
make jwt-keys
```

Cette commande génère une paire RSA 4096 bits dans `services/auth/config/jwt/` et copie la clé publique dans `services/rh/config/jwt/` et `services/ops/config/jwt/`.

## 3. Démarrer l'environnement complet (10 min)

```bash
make setup
```

`make setup` exécute dans l'ordre :
1. `docker compose build` — construit les images des 3 services
2. `docker compose up -d` — démarre tous les containers
3. `make install` — `composer install` dans les 3 services
4. `make migrate` — migrations PostgreSQL des 3 services

## 4. Vérifier que tout fonctionne (2 min)

```bash
# Health checks
curl http://localhost/health           # → nginx → auth
curl http://localhost/api/rh/health    # → nginx → rh (à adapter selon les routes)
curl http://localhost/api/ops/health   # → nginx → ops

# Accès aux outils dev
open http://localhost:8025   # MailHog (emails)
open http://localhost:9001   # MinIO console
```

## 5. Tester l'inscription (3 min)

```bash
curl -X POST http://localhost/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "companyName": "BTP Lomé SARL",
    "sector": "BTP",
    "email": "admin@btp-lome.com",
    "password": "Password123!",
    "firstName": "Kofi",
    "lastName": "Mensah"
  }'
```

Réponse attendue : `201 Created` avec le JWT et le refresh token.

## Structure du projet

```
equencia-backend/
├── services/
│   ├── auth/    → Port 8001 — JWT, tenants, users
│   ├── rh/      → Port 8002 — RH complet
│   └── ops/     → Port 8003 — Inspections, docs, notifs
├── packages/
│   └── shared/  → Package Composer partagé (ValueObjects, Contracts)
├── infrastructure/
│   ├── docker-compose.yml
│   ├── nginx/   → Reverse proxy local
│   ├── traefik/ → Gateway production
│   └── postgres/→ Init SQL (RLS, extensions)
├── docs/
│   ├── ONBOARDING.md    ← ce fichier
│   ├── ARCHITECTURE.md
│   └── adr/             → Décisions d'architecture
└── Makefile             → Point d'entrée unique
```

## Commandes utiles au quotidien

```bash
make dev              # Démarrer tous les containers
make stop             # Arrêter
make logs             # Logs de tous les services
make logs-auth        # Logs du service auth uniquement

make sh-auth          # Shell dans le container auth
make sh-rh            # Shell dans le container rh
make sh-ops           # Shell dans le container ops
make sh-db            # Shell psql

make migrate          # Migrations des 3 services
make migration-diff-rh  # Générer une migration rh depuis les entités

make test             # Tests des 3 services
make lint             # php-cs-fixer sur les 3 services
make phpstan          # PHPStan level 8 sur les 3 services

make db-reset         # DANGER : reset complet de la DB
```

## Flux de développement typique

1. Modifier une entité dans `services/rh/src/Domain/Entity/`
2. `make migration-diff-rh` → générer la migration
3. `make migrate-rh` → appliquer
4. Implémenter le Command/Handler dans `Application/Command/`
5. Implémenter le Repository dans `Infrastructure/Persistence/`
6. Exposer via un Controller ou une API Platform Resource dans `Interface/`
7. `make test-rh` avant de commit

## Troubleshooting

**Erreur `composer install` — package equencia/shared introuvable**  
→ Vérifier que `packages/shared/` existe et contient un `composer.json` valide. Le `"type": "path"` dans chaque service pointe vers `../../packages/shared`.

**JWT invalide dans rh ou ops**  
→ Vérifier que `make jwt-keys` a bien copié `public.pem` dans `services/rh/config/jwt/` et `services/ops/config/jwt/`.

**Erreur RLS `permission denied`**  
→ Le `TenantMiddleware` n'a pas positionné `app.current_tenant_id`. Vérifier que le JWT contient bien `tenant_id` dans le payload.
