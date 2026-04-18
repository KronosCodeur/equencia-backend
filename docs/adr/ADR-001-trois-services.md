# ADR-001 — Architecture 3 services au lieu de 9

**Date :** 2025-04  
**Statut :** Accepté

## Contexte

La spec initiale découpait le backend en 9 microservices distincts (auth, employees, attendance, planning, inspection, leaves, payroll, notification, document). Pour un développeur solo démarrant en V1, ce découpage génère une complexité opérationnelle disproportionnée : 9 bases de données, 9 pipelines CI, 9 Dockerfiles, 9 `composer install` à maintenir.

## Décision

Regrouper les 9 services en 3 :

| Service | Port | Domaines couverts |
|---------|------|-------------------|
| `auth` | 8001 | JWT, tenants, users, RBAC |
| `rh` | 8002 | Employés, présences, planning, congés, paie |
| `ops` | 8003 | Inspections, documents PDF, notifications |

Les entités restent dans des **bounded contexts distincts** à l'intérieur de chaque service (sous-dossiers `Domain/Entity/`), ce qui préserve la modularité interne sans multiplier les services.

## Conséquences

**Positif :**
- 1 `docker compose up` pour tout démarrer
- 3 bases de données PostgreSQL au lieu de 9 (shared DB + tenant isolation via RLS)
- Complexité opérationnelle réduite pour un solo developer
- Extraction future facilitée : chaque bounded context peut devenir un service indépendant si le besoin se présente

**Négatif :**
- Le service `rh` est plus large — sa taille devra être surveillée
- Un incident dans `rh` affecte plusieurs domaines métier simultanément

## Alternatives considérées

- **9 services** : rejeté (overhead solo dev)
- **1 service monolithique** : rejeté (couplage fort, migration future difficile)
