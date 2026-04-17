Review code Symfony sur les fichiers modifiés (git diff).

Focus :
1. Bugs logiques
2. Vulnérabilités sécurité (injection, auth bypass, XSS)
3. N+1 queries Doctrine
4. Violations architecture hexagonale (Doctrine dans Domain, logique dans Controller)
5. Types manquants ou trop larges
6. Performance (cache, requêtes lourdes)

NE PAS commenter : style (PHP-CS-Fixer), absence commentaires (voulu), PHPStan warnings.

Format : [CRITIQUE/MOYEN/MINEUR] fichier:ligne — Description + Fix
Max 10 items, priorisés.
