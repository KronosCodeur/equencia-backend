Génère ou met à jour la documentation README pour: $ARGUMENTS

Si vide, parcourir tous les dossiers src/ et générer/mettre à jour.

## Format README par dossier
```markdown
# [Feature] — [Domain/Application/Infrastructure]

[Description courte]

## Fichiers
- `Entity/Order.php` → Entité métier commande
- `Repository/OrderRepositoryInterface.php` → Port repository
- `ValueObject/OrderStatus.php` → Enum statuts commande
```

## README racine
```markdown
# [Projet]

## Documentation
- [Order Domain](src/Domain/Order/README.md)
- [Order Application](src/Application/Order/README.md)
- [Controllers](src/Infrastructure/Http/Controller/README.md)
```

Chaque nouveau fichier → ajouté au README du dossier.
Chaque nouveau README → référencé dans le README racine.
