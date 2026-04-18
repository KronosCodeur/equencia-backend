# ADR-003 — JWT RS256 avec clés asymétriques

**Date :** 2025-04  
**Statut :** Accepté

## Contexte

L'architecture 3-services nécessite que `rh` et `ops` puissent valider les JWT émis par `auth` sans avoir accès au secret de signature. Deux algorithmes sont courants :

- **HS256** : secret symétrique partagé — si `rh` connaît le secret, il peut aussi émettre des tokens
- **RS256** : paire de clés asymétrique — `auth` signe avec la clé privée, les autres services valident avec la clé publique uniquement

## Décision

Utiliser **RS256** avec :
- Clé privée PEM uniquement dans `services/auth/config/jwt/private.pem`
- Clé publique PEM copiée dans `services/rh/config/jwt/public.pem` et `services/ops/config/jwt/public.pem`
- `make jwt-keys` génère la paire et distribue la clé publique automatiquement
- `LexikJWTAuthenticationBundle` configuré : `secret_key: ~` dans rh et ops (pas de clé privée)

## Payload JWT

```json
{
  "sub": "user-uuid",
  "tenant_id": "tenant-uuid",
  "tenant_slug": "acacia-btp",
  "role": "manager",
  "plan": "business",
  "email": "user@example.com",
  "iat": 1714000000,
  "exp": 1714000900
}
```

TTL : 15 minutes. Refresh token httpOnly cookie, TTL 7 jours.

## Conséquences

**Positif :**
- Principe du moindre privilège : rh/ops ne peuvent pas forger de tokens
- Validation stateless dans chaque service (pas d'appel réseau vers auth)
- Révocation possible via blacklist Redis dans auth (pour les cas critiques)

**Négatif :**
- La clé publique doit être re-distribuée si elle change (rotation annuelle recommandée)
- `make jwt-keys` doit être exécuté une seule fois à l'initialisation

## Rotation des clés

En production : stocker les clés dans un secret manager (Vault, AWS Secrets Manager). La rotation implique un déploiement coordonné des 3 services avec la nouvelle clé publique.
