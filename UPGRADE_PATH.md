Reference: https://tomasvotruba.com/blog/off-the-beaten-path-to-upgrade-symfony-28-to-72

doctrine/doctrine-bundle:
- 1.10.3 requires symfony/dependency-injection: ~2.7|~3.0|~4.0
- 1.12.13 requires symfony/dependency-injection: ^3.4.30|^4.3.3
- 2.0.0 requires symfony/dependency-injection: ^4.3.3|^5.0
- 2.4.0 requires symfony/dependency-injection: ^4.3.3|^5.0|^6.0
- 2.11.0 requires symfony/dependency-injection: ^5.4 || ^6.0 || ^7.0
- 2.14.0 requires symfony/dependency-injection: ^6.4 || ^7.0
- 3.0.0 requires symfony/dependency-injection: ^6.4 || ^7.0
- 3.1.0 requires symfony/dependency-injection: ^6.4 || ^7.0 || ^8.0
- 3.3.2 requires symfony/dependency-injection: ^6.4 || ^7.0 || ^8.0

doctrine/orm:
- 2.5.14 requires php: >=5.4 and symfony/console: ~2.5|~3.0|~4.0
- 2.13.0 requires php: ^7.1 || ^8.0 and symfony/console: ^3.0 || ^4.0 || ^5.0 || ^6.0

phpstan/phpstan:
- 2.2.16 requires php: ^7.4|^8.0

phpoffice/phpspreadsheet:
- 1.30.7 requires php: >=7.4.0 <8.5.0
- 1.19.0 requires php: ^7.2 || ^8.0
- 1.12.0 requires php: ^7.1

composer/composer:
- 2.10.3 requires php: ^7.2.5 || ^8.0

doctrine/doctrine-migrations-bundle:
- 1.3.2 requires php: >=5.4.0 and symfony/framework-bundle: ~2.7|~3.3|~4.0
- 2.2.3 requires php: ^7.1|^8.0 and symfony/framework-bundle: ~3.4|~4.0|~5.0

friendsofsymfony/user-bundle:
- 2.0.2 requires php: ^5.5.9 || ^7.0 and symfony/security-bundle: ^2.7 || ^3.0
- 2.1.2 requires php: ^5.5.9 || ^7.0 and symfony/security-bundle: ^2.7 || ^3.0
- 2.2.0 requires symfony/security-bundle: ^4.4
- 3.0.0 requires symfony/security-bundle: ^4.4 || ^5.0
- 3.1.0 requires symfony/security-bundle: ^4.4 || ^5.0 || ^6.0
- 4.0.0 requires symfony/security-bundle: ^6.4 || ^7.0

symfony/framework-bundle:
- 5.4.0 requires php: >=7.2.5
- 6.0.0 requires php: >=8.0.2
- 6.1.0 requires php: >=8.1
- 6.2.0 requires php: >=8.1
- 6.3.0 requires php: >=8.1
- 6.4.0 requires php: >=8.1
- 7.0.0 requires php: >=8.2
- 7.1.0 requires php: >=8.2
- 7.2.0 requires php: >=8.2
- 7.3.0 requires php: >=8.2
- 7.4.0 requires php: >=8.2
- 8.0.0 requires php: >=8.4
- 8.1.0 requires php: >=8.4.1

1. Symfony 3.0
2. Doctrine ORM 2.13
3. PHP 7.4 (local; prod is already there)
4. composer 2.10
4. phpstan/phpstan 2.2
5. phpoffice/phpspreadsheet 1.30
6. Symfony 3.4
7. doctrine/doctrine-bundle 1.12
8. doctrine/doctrine-migrations-bundle 2.2.3
8. DEPLOYMENT TO PRODUCTION : PHP 7.4, Symfony 3.4
9. Symfony 4.4
10. Symfony 5.4
14. DEPLOYMENT TO PRODUCTION : PHP 7.4, Symfony 5.4
12. PHP 8.4
15. Remove friendsofsymfony/user-bundle
14. DEPLOYMENT TO PRODUCTION : PHP 8.4, Symfony 5.4
11. Symfony 6.4
11. Symfony 7.4
12. Symfony 8.1
14. DEPLOYMENT TO PRODUCTION : PHP 8.4, Symfony 8.1


1. Removals (OCTGN, twig/extensions, the scrapers, /api/doc depending on the decision).
2. Symfony 3.4 + ORM 2.6/2.7 + local PHP 7.4 (aligned with prod), then deploy.
3. PhpSpreadsheet, Assetic replaced, the bundle upgrades from point 4, Composer 2 and phpstan 2.
4. Symfony 4.4, then deploy.
5. Symfony 5.4 + ORM 2.13+ + FOSUser replaced, then deploy.
6. PHPUnit 9, then PHP 8.2 or 8.3, then deploy.
7. Symfony 6.4, then deploy.
8. Symfony 7.4 + PHP 8.4, then deploy.

