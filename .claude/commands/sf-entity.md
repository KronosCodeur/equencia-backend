Crée une entité complète pour: $ARGUMENTS

## À générer
1. Domain/[Feature]/Entity/[Nom].php → Entité métier pure, propriétés typed readonly
2. Domain/[Feature]/Repository/[Nom]RepositoryInterface.php → Interface port
3. Infrastructure/Doctrine/Entity/[Nom].php → Mapping Doctrine (#[ORM\...], Gedmo Timestampable)
4. Infrastructure/Doctrine/Repository/Doctrine[Nom]Repository.php → Implémentation
5. Migration Doctrine (php bin/console make:migration)

## Règles
- declare(strict_types=1)
- Attributs PHP 8, jamais annotations
- DateTimeImmutable par défaut
- Index sur les colonnes fréquemment filtrées
- Gedmo\Timestampable(on: 'create') et (on: 'update')
- ZERO commentaire
