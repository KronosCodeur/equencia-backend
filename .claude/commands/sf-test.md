Génère les tests PHPUnit pour: $ARGUMENTS

## À générer
### Unit Test (Handler/Service)
- final class avec TestCase
- Pattern AAA : Arrange → Act → Assert
- Min 1 test success + 1 test failure
- Mocks via createMock()
- Nommage : test[Action][ExpectedResult]

### Functional Test (Controller)
- final class avec WebTestCase
- Tester chaque endpoint : status code + structure réponse
- Test avec auth JWT valide
- Test sans auth (401)
- Test avec données invalides (422)

## Règles
- declare(strict_types=1)
- ZERO commentaire
- Tests indépendants
- 1 test = 1 comportement
