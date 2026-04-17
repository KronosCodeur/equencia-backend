Crée un DTO complet pour: $ARGUMENTS

## À générer
### DTO Request (si write)
```php
final readonly class Create[Nom]Request
{
    public function __construct(
        #[Assert\NotBlank] public string $field1,
        #[Assert\Positive] public int $field2,
    ) {}

    public static function fromRequest(Request $request): self { ... }
}
```

### DTO Response
```php
final readonly class [Nom]DTO
{
    public function __construct(
        public int $id,
        public string $field1,
        public string $createdAt,
    ) {}

    public static function fromEntity([Nom] $entity): self { ... }
    public function toArray(): array { ... }
}
```

## Règles
- final readonly class
- Assert constraints sur les request DTO
- fromRequest() / fromEntity() factory methods
- toArray() pour la sérialisation
- ZERO commentaire
