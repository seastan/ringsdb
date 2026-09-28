
0. Compléter l'inventaire des dépréciations

Le rapport de dépréciations de l'autre jour ratait une partie du problème. Twig ne signale une dépréciation qu'en compilant un template, et le cache de test était déjà chaud : j'ai trouvé form_enctype(), supprimé en 3.0, dans 22 templates, alors qu'il n'apparaissait pas dans le rapport. Même chose pour les dépréciations de configuration, qui ne sont signalées qu'à la compilation du container. Je relancerais donc :
- la suite avec le cache vidé, avec SYMFONY_DEPRECATIONS_HELPER=max[total]=999999 ;
- lint:twig sur tous les templates.

1. En Symfony 2.8, déployable tel quel

- form_enctype() : 16 templates des CRUD admin, et 6 surcharges FOSUser (ChangePassword, Group ×2, Profile, Registration, Resetting). Je le remplacerais par ce que la fonction affichait : {% if form.vars.multipart %}enctype="multipart/form-data"{% endif %}.
    - Les surcharges FOSUser datent de FOSUser 1.x. Les templates Group/* sont probablement morts, puisqu'on n'utilise pas les groupes : je propose de les supprimer.
- ScrapCardDataCommand (app:cgdb:cards) utilise DialogHelper, supprimé en 3.0. Soit on le passe à QuestionHelper, soit on supprime la commande, ce qui règle au passage une des « commandes de scraping » de la roadmap. Ma préférence : la supprimer, si personne ne s'en sert.
- Ce que l'étape 0 fera ressortir.
- Pour le reste, j'ai cherché les suppressions habituelles de la 3.0 dans notre code, et il est propre : pas de getRequest() ni get('request'), pas de routes en pattern:, _method ou _scheme, pas de scope ni de factory_* dans les services, pas de SecurityContext, ni de classes supprimées (AbstractVoter, ContainerAware, Assert\True…). Les form types et security.context sont déjà faits.

2. La montée elle-même

- composer.json :

| Paquet                                    | Contrainte                              |
|-------------------------------------------|-----------------------------------------|
| symfony/symfony                           | 3.0.*                                   |
| symfony/css-selector, symfony/dom-crawler | à retirer (inclus dans symfony/symfony) |
| sensio/distribution-bundle                | ^5.0                                    |
| sensio/generator-bundle                   | ^3.0                                    |
| symfony/monolog-bundle                    | ^2.8                                    |
| symfony/swiftmailer-bundle                | ^2.3 suffit (2.6 accepte ~3.0)          |
Les autres bundles du lock acceptent déjà Symfony 3 : FOSUser 2.1.2, Assetic 2.8, LiuggioExcel, SensioFrameworkExtra 3.0, FOSJsRouting 1.6, Nelmio 2.13, DoctrineBundle 1.10 et les fixtures 2.4.
- Un composer update ciblé : symfony/symfony et les paquets ci-dessus, avec --with-dependencies, en Composer 2, puis une relecture du diff du lock. C'est le premier vrai composer update du projet. Ensuite, vendor/ ne peut plus être recopié du serveur : il faut composer install au déploiement. C'est un changement de process à prévoir avec cette mise en prod (voir question 3).
- Garder la structure 2.x : app/cache, app/logs, app/console, web/. Symfony 3 l'accepte, et passer à var/, bin/console ne ferait que du bruit à ce stade. Les front controllers (ApcClassLoader, loadClassCache) marchent encore en 3.0.
- Corriger ce que les tests font tomber, puis relancer phpstan. Mes extensions lisent le dump XML du container, que Symfony 3.0 produit toujours.

3. Vérifier, puis déployer

- La suite complète, phpstan, les dépréciations 3.0 (en vue de la 3.4), et un tour manuel sur l'app locale pour ce que les tests ne couvrent pas : les assets Assetic et /api/doc.
- Déployer en prod, où tu es déjà en PHP 7.4.

Ensuite, hors de cette étape

Doctrine ORM, pour débloquer PHP 7.4 en local (doctrine/orm 2.6+ demande symfony/console 3). Je maintiens ma réserve sur 2.13 : il entraîne probablement doctrine/common 3, et donc Gedmo 3, data-fixtures et SensioFrameworkExtra. 2.6 ou 2.7 serait le pas minimal. Je vérifierais avec composer why-not doctrine/orm 2.13 au moment de le faire.

Questions avant de commencer

1. ScrapCardDataCommand : suppression, ou passage à QuestionHelper ?
2. Les surcharges FOSUser Group/* : je les supprime ?
3. Le déploiement : comment vendor/ arrive-t-il en prod aujourd'hui ? Si c'est une copie depuis le poste de dev, le composer update change la façon de déployer.
