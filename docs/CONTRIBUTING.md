# Guide de contribution — Equencia Backend

## Branches principales

| Branche | Rôle | Protection |
|---------|------|------------|
| `main` | Code en production — chaque commit est taggé | Protégée, merge uniquement via MR |
| `stg` | Pré-production / recette | Protégée, merge uniquement via MR |
| `dev` | Intégration des fonctionnalités en cours | Protégée, merge uniquement via MR |

```
main   ●────────●────────●──────────●  (tags: v1.0.0, v1.1.0, v1.1.1)
        ↑        ↑                   ↑
stg    ●────●───●────●──────●───────●  (validation / recette)
        ↑            ↑              ↑
dev    ●──●──●──●───●──●──●──●─────●  (intégration continue)
          ↑  ↑  ↑       ↑  ↑
          f1 f2 f3      f4 f5          (feature branches)
```

## Versionnement

Le projet suit le [Semantic Versioning](https://semver.org/) avec le suffixe `-SNAPSHOT` pour les versions en développement.

| Phase | Format | Exemple |
|-------|--------|---------|
| Développement | `x.y.z-SNAPSHOT` | `1.2.0-SNAPSHOT` |
| Release (production) | `x.y.z` | `1.2.0` |

- **MAJOR (x)** : changements non rétrocompatibles (breaking API)
- **MINOR (y)** : nouvelles fonctionnalités rétrocompatibles
- **PATCH (z)** : corrections de bugs

La version courante est déclarée dans chaque `services/*/composer.json` sous la clé `"version"`.

## Workflows

### 1. Nouvelle fonctionnalité

```bash
git checkout dev && git pull origin dev
git checkout -b feature/ma-fonctionnalite
```

Nommer la branche : `feature/<description-courte>` (ex : `feature/employee-qr-badge`, `feature/payslip-pdf`).

Créer une MR : `feature/ma-fonctionnalite` → `dev`.

**Checklist avant merge :**
- [ ] `make lint` passe (PHP-CS-Fixer)
- [ ] `make phpstan` passe (level 8)
- [ ] `make test` passe avec couverture ≥ 80 %
- [ ] Migration générée si entité modifiée (`make migration-diff-<service>`)
- [ ] La branche est à jour avec `dev`

La branche `feature/*` est supprimée après le merge.

---

### 2. Nouvelle release

**Étape 1 — MR `dev` → `stg`**

Déploiement automatique en pré-production. Valider la recette.

**Étape 2 — Finaliser la version dans `stg`**

```bash
git checkout stg && git pull origin stg

# Dans chaque services/*/composer.json : "1.2.0-SNAPSHOT" → "1.2.0"
# Exemple :
sed -i '' 's/"1.2.0-SNAPSHOT"/"1.2.0"/' services/auth/composer.json
sed -i '' 's/"1.2.0-SNAPSHOT"/"1.2.0"/' services/rh/composer.json
sed -i '' 's/"1.2.0-SNAPSHOT"/"1.2.0"/' services/ops/composer.json

git add services/*/composer.json
git commit -m "chore: release 1.2.0"
git push origin stg
```

**Étape 3 — MR `stg` → `main`**

**Étape 4 — Créer le tag et préparer le prochain cycle**

```bash
git checkout main && git pull origin main
git tag -a v1.2.0 -m "Release v1.2.0"
git push origin v1.2.0

# Bumper la version sur dev
git checkout dev && git pull origin dev
# "1.2.0" → "1.3.0-SNAPSHOT" dans les composer.json
git add services/*/composer.json
git commit -m "chore: bump version to 1.3.0-SNAPSHOT"
git push origin dev
```

---

### 3. Hotfix

```bash
# Partir du tag concerné
git checkout v1.2.0
git checkout -b hotfix/1.2.1

# Corriger, committer
git commit -m "fix: description du correctif"

# MR hotfix/1.2.1 → stg (recette) puis stg → main
git checkout main && git pull origin main
git tag -a v1.2.1 -m "Hotfix v1.2.1"
git push origin v1.2.1

# Reporter sur dev
git checkout dev
git cherry-pick <commit-hash>
git push origin dev
```

La branche `hotfix/*` est supprimée après les merges.

---

## Conventions de commits

Suivre [Conventional Commits](https://www.conventionalcommits.org/).  
**Messages en français. Un fichier = un commit. Zéro `Co-Authored-By`.**

```
<type>: <description en français>
```

| Type | Usage |
|------|-------|
| `feat` | Nouvelle fonctionnalité |
| `fix` | Correction de bug |
| `docs` | Documentation |
| `refactor` | Refactoring sans changement de comportement |
| `test` | Ajout ou modification de tests |
| `chore` | Maintenance (dépendances, CI, config, migration) |

**Exemples :**
```
feat: ajouter la génération du badge QR employé
fix: corriger le calcul des jours de congé (weekends exclus)
chore: générer la migration pour la table payslips
test: ajouter les tests unitaires de LeaveRequest
```

## Pipeline CI

Chaque MR déclenche :

1. `make lint` — PHP-CS-Fixer (PSR-12 strict)
2. `make phpstan` — analyse statique level 8
3. `make test` — PHPUnit avec seuil de couverture 80 %
4. Build Docker des services modifiés

Le merge est bloqué si le pipeline échoue.
