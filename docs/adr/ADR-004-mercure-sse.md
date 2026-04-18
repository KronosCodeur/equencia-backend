# ADR-004 — Mercure SSE pour le temps réel dashboard

**Date :** 2025-04  
**Statut :** Accepté

## Contexte

Le dashboard Manager nécessite des mises à jour temps réel : pointages en direct, statuts inspections, alertes absences. Plusieurs approches sont possibles :

- **Polling HTTP** : simple mais inefficace (100 clients × 1 req/s = 100 req/s inutiles)
- **WebSockets** : bidirectionnel mais complexe à scaler avec load balancer
- **SSE (Server-Sent Events)** : unidirectionnel serveur→client, HTTP standard, reconnexion automatique
- **Mercure** : hub SSE avec JWT intégré, authorization par topic, compatible Symfony

## Décision

Utiliser **Mercure** (hub Go open-source) comme broker SSE :

- Le hub Mercure tourne comme container Docker séparé
- Les services Symfony publient des événements via HTTP POST au hub
- Le frontend Nuxt s'abonne via `EventSource` avec un JWT Mercure
- Les topics sont préfixés par tenant : `/tenants/{tenant_id}/attendances`

```
services/rh → POST /publish → mercure hub → SSE → frontend nuxt
```

Le JWT Mercure est distinct du JWT applicatif : `auth` émet un token Mercure signé avec `MERCURE_JWT_SECRET` lors du login, limité aux topics du tenant de l'utilisateur.

## Conséquences

**Positif :**
- Aucune logique WebSocket à maintenir
- Reconnexion automatique built-in dans SSE
- Mercure gère le fan-out (N clients d'un tenant)
- Mode dev : Mercure intégré dans Docker Compose

**Négatif :**
- Un service tiers supplémentaire à opérer
- SSE est unidirectionnel : pour les actions utilisateur on continue d'utiliser l'API REST
- En production, Mercure doit être derrière Traefik avec TLS

## Topics utilisés

| Topic | Producteur | Consommateur |
|-------|-----------|--------------|
| `/tenants/{id}/attendances` | rh-service | Dashboard présences |
| `/tenants/{id}/inspections` | ops-service | Dashboard inspections |
| `/tenants/{id}/notifications` | ops-service | Notifications in-app |
