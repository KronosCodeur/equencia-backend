# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Stack

Symfony 8.0 · PHP 8.4 · API Platform 4 · PostgreSQL 16 · Redis 7 · Mercure · Gotenberg (PDF)

## Commandes

```bash
# Base de données (Docker)
docker compose up -d
docker compose down

# Dépendances
composer install

# Migrations
php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:diff     # générer depuis les entités
php bin/console doctrine:migrations:status

# Tests
php bin/phpunit
php bin/phpunit --filter NomDuTest           # test ciblé
php bin/phpunit tests/Unit/Domain/           # dossier ciblé

# Console Symfony
php bin/console cache:clear
php bin/console debug:router
php bin/console messenger:consume async      # consommer les messages Redis

# Qualité
composer run php-cs-fixer
composer run phpstan
```

## Architecture Hexagonale + CQRS

```
src/
├── Domain/          ← Cœur métier — ZÉRO dépendance framework
│   ├── Entity/      ← Entités riches (logique métier, Domain Events)
│   ├── ValueObject/ ← Objets valeur immuables (EmployeeId, TenantId, QrCodeHash…)
│   ├── Repository/  ← Interfaces (ports uniquement — pas d'implémentation)
│   ├── Event/       ← Domain Events (EmployeeCreated, AttendanceRecorded…)
│   ├── Exception/   ← Exceptions métier typées
│   └── Service/     ← Domain Services (logique qui ne tient pas dans une entité)
│
├── Application/     ← Orchestration — use cases CQRS
│   ├── Command/     ← Écriture : [Action]Command.php + [Action]Handler.php
│   ├── Query/       ← Lecture  : [Action]Query.php  + [Action]Handler.php
│   ├── DTO/         ← Data Transfer Objects (entrée/sortie des use cases)
│   └── EventHandler/← Réaction aux Domain Events
│
├── Infrastructure/  ← Adaptateurs concrets (framework autorisé ici)
│   ├── Persistence/ ← DoctrineXxxRepository implémentant les interfaces Domain
│   ├── Messenger/   ← Message handlers async (Symfony Messenger)
│   ├── External/    ← Clients HTTP tiers (MinIO, Gotenberg, Mercure…)
│   └── Storage/     ← Adaptateur S3/MinIO
│
└── Interface/       ← Points d'entrée applicatifs
    ├── Api/         ← API Platform Resources (#[ApiResource])
    ├── Controller/  ← Controllers Symfony custom
    └── Serializer/  ← Normalizers/Denormalizers
```

**Règles d'or :**
- `Domain/` n'importe jamais `Infrastructure/` ni `Interface/`
- `Application/` n'importe jamais `Infrastructure/`
- Les Command/Query Handlers reçoivent les interfaces du Domain, pas les implémentations
- Toute communication inter-services passe par **Redis (Messenger)** ou **HTTP REST** — jamais d'appel direct

## Multi-Tenancy

Chaque tenant = un schema PostgreSQL dédié. Le JWT contient le `tenant_id`. `TenantMiddleware` (`Infrastructure/EventListener/TenantMiddleware.php`) injecte le `TenantId` dans `TenantContext` à chaque requête. Toutes les requêtes Doctrine doivent filtrer sur `tenantId` — ne jamais omettre ce filtre.

## Patterns Clés

### Entité Domain
- Constructeur privé + named constructor statique `create()`
- `pullDomainEvents(): array` pour collecter les événements à dispatcher
- Readonly properties pour l'identité, mutables pour l'état

### Value Object
- `final readonly class`
- Validation dans le constructeur, exception métier si invalide
- Named constructor `from()` ou `generate()` selon le cas

### Repository
- Interface dans `Domain/Repository/` — méthodes nommées métier (`findByQrHash`, `findAll(TenantId, Filters)`)
- Implémentation `DoctrineXxxRepository` dans `Infrastructure/Persistence/` — filtre toujours sur `tenantId`

### Command Handler
```php
final class CreateEmployeeHandler
{
    public function __construct(
        private readonly EmployeeRepositoryInterface $repository,
        private readonly EventDispatcherInterface $dispatcher,
    ) {}

    public function __invoke(CreateEmployeeCommand $command): EmployeeId { ... }
}
```

## Configuration Importante

- **JWT** : routes publiques déclarées dans `config/packages/security.yaml` (`^/api/auth/login`, `^/api/auth/register`)
- **Messenger** : transport Redis `stream: equencia_messages`, group `equencia_workers`, retry x3
- **Doctrine ORM** : entités mappées depuis `src/Domain/Entity/` (pas `src/Entity/`)
- **CORS** : configuré via `CORS_ALLOW_ORIGIN` dans `.env`

## Variables d'Environnement

Copier `.env` → `.env.local` pour les surcharges locales. Ne jamais committer `.env.local`.

```
DATABASE_URL=postgresql://app:!ChangeMe!@127.0.0.1:5432/app?serverVersion=16&charset=utf8
REDIS_URL=redis://localhost:6379
CORS_ALLOW_ORIGIN='^https?://(localhost|127\.0\.0\.1)(:[0-9]+)?$'
```

## Workflow — Implémenter une feature

Suivre ces étapes dans l'ordre. Ne pas passer à l'étape suivante sans avoir terminé la précédente.

1. **Branche** — Vérifier qu'une branche `feature/<nom>` existe depuis `dev`. Sinon, la créer.
2. **Identifier le service** — Quel service est concerné ? (`auth` / `rh` / `ops`). Si la feature touche le Domain partagé, passer par `packages/shared/`.
3. **Domain en premier** — Créer ou modifier l'entité + exceptions + interface Repository. Aucune dépendance framework ici.
4. **Application** — Créer le Command ou Query + son Handler. Le Handler ne reçoit que des interfaces Domain.
5. **Infrastructure** — Implémenter le DoctrineRepository. Filtrer systématiquement sur `tenantId`.
6. **Interface** — Exposer via API Platform Resource ou Controller. Valider les entrées via DTO + `#[Assert\...]`.
7. **Tests** — Écrire les tests unitaires (Domain) et fonctionnels (Controller). Seuil minimum : **80 % de couverture**.
8. **Qualité** — `make lint` (PHP-CS-Fixer) + `make phpstan` (level 8) + `make test`. Tout doit passer.
9. **Migration** — Si une entité a changé : `make migration-diff-<service>` + relire le SQL généré avant de committer.
10. **Commits** — Un fichier = un commit. Messages en français, Conventional Commits. Zéro `Co-Authored-By`.

## Conventions de commits

Suivre [Conventional Commits](https://www.conventionalcommits.org/).  
**Messages en français. Un fichier = un commit. Zéro `Co-Authored-By`.**

```
feat: ajouter la génération du badge QR employé
fix: corriger le calcul des jours de congé (weekends exclus)
chore: générer la migration pour la table payslips
test: ajouter les tests unitaires de LeaveRequest
```

## Tests — Seuils et règles

- Couverture minimale : **80 % (lignes)** sur `src/Domain/` et `src/Application/`
- `src/Infrastructure/` : tests fonctionnels (pas de mock Doctrine — tester avec la vraie DB)
- Chaque entité Domain doit avoir ses tests unitaires avant d'être considérée terminée
- Les Command Handlers sont testés avec des doubles (mock) des interfaces Repository

```bash
# Lancer avec rapport de couverture
php bin/phpunit --coverage-text
php bin/phpunit --coverage-html var/coverage
```

## Branches et versionnement

Voir [docs/CONTRIBUTING.md](docs/CONTRIBUTING.md) pour le guide complet.

| Branche | Rôle |
|---------|------|
| `main` | Production — taggée SemVer (`v1.2.0`) |
| `stg` | Pré-production / recette |
| `dev` | Intégration continue |
| `feature/*` | Fonctionnalités en cours |
| `hotfix/*` | Correctifs urgents depuis un tag |
