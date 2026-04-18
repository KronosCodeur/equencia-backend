# ADR-002 — Shared Database + Row Level Security pour le multi-tenancy

**Date :** 2025-04  
**Statut :** Accepté

## Contexte

Equencia est un SaaS multi-tenant : chaque entreprise (tenant) ne doit voir que ses propres données. Trois stratégies existent :

1. **Database per tenant** : isolation maximale, coût infrastructure élevé
2. **Schema per tenant** : bonne isolation, migrations complexes (N schemas)
3. **Shared DB + tenant_id + RLS** : simplicité opérationnelle, isolation garantie par le moteur DB

## Décision

Utiliser une **base de données partagée** avec :
- Colonne `tenant_id UUID NOT NULL` sur toutes les tables métier
- **PostgreSQL Row Level Security (RLS)** activé sur chaque table
- Variable de session `app.current_tenant_id` positionnée par `TenantMiddleware` à chaque requête
- Fonction SQL `current_tenant_id()` qui lit cette variable de session

La politique RLS :
```sql
CREATE POLICY tenant_isolation ON ma_table
    USING (tenant_id = current_tenant_id());
```

L'isolation fonctionne à **3 couches** :
1. Application : `TenantMiddleware` filtre via `TenantContext`
2. Repository : chaque `find*` inclut `WHERE tenant_id = :tenantId`
3. Base de données : RLS bloque toute requête sans le bon `app.current_tenant_id`

## Conséquences

**Positif :**
- Une seule base PostgreSQL à opérer par service
- Migrations centralisées (un seul schema)
- RLS comme filet de sécurité si une couche applicative faillit

**Négatif :**
- Performance : RLS ajoute une évaluation de politique sur chaque ligne
- Complexité des requêtes analytiques cross-tenant (réservé SuperAdmin)
- Un bug dans `TenantMiddleware` peut potentiellement exposer des données si RLS est aussi absent

## Mitigation

Les workers Messenger (async) positionnent aussi `app.current_tenant_id` avant traitement. Les jobs batch SuperAdmin utilisent un rôle PostgreSQL bypassant le RLS uniquement pour les rapports agrégés anonymisés.
