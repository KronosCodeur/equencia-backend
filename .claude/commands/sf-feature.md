Crée une feature Symfony complète en architecture hexagonale + CQRS pour: $ARGUMENTS

## Structure à générer

### Domain/[Feature]/
- `Entity/[Nom].php` → Entité métier pure (ZERO dépendance Doctrine)
- `Repository/[Nom]RepositoryInterface.php` → Interface port
- `ValueObject/` → Si objets valeur nécessaires
- `Exception/[Nom]NotFoundException.php` → Exception métier
- `README.md`

### Application/[Feature]/
- `Command/Create[Nom]Command.php` → Commande write
- `Query/Get[Nom]Query.php` → Requête read
- `Handler/Create[Nom]Handler.php` → Handler write (#[AsMessageHandler])
- `Handler/Get[Nom]Handler.php` → Handler read
- `DTO/[Nom]DTO.php` → DTO réponse
- `DTO/Create[Nom]Request.php` → DTO requête avec validation
- `README.md`

### Infrastructure/
- `Doctrine/Entity/[Nom].php` → Entité Doctrine (mapping ORM)
- `Doctrine/Repository/Doctrine[Nom]Repository.php` → Implémente l'interface Domain
- `Http/Controller/[Nom]Controller.php` → Endpoints REST thin
- `README.md` (par sous-dossier)

### Tests/
- `Unit/Application/[Feature]/Handler/Create[Nom]HandlerTest.php`
- `Functional/Infrastructure/Http/Controller/[Nom]ControllerTest.php`

## Règles
- declare(strict_types=1) dans CHAQUE fichier
- final readonly class par défaut
- Attributs PHP 8 partout
- ZERO commentaire
- Gedmo\Timestampable sur l'entité Doctrine
- Migration Doctrine générée
- DTO avec Assert constraints
- Voter si autorisation nécessaire
