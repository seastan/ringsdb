# Migration Symfony 2.8 → 7.4 / PHP 8.5

Migration notes. Starting point: Symfony 2.8.52, FOSUserBundle 2.0.2, PHP 7.1.33. Current state:
Symfony 3.4.49, FOSUserBundle 2.1.2, PHP 7.4.33, the same PHP version as production (see
"Progress").

The functional tests (`make phpunit`) are the safety net: they must stay green at every step.

- **Roadmap**: what is left to do before the migration, by priority.
- **Migration plan**: what the migration itself involves (steps done, dependencies, assets, tests,
  environment).
- **Reference**: the behaviour pinned by the tests, area by area, with the bugs and quirks found.
  The roadmap links to it by section name.

# Roadmap

## 1. Removals (decided, to do first)

Each removal reduces what has to be ported.

- Done: the **OAuth2 server** and **GregwarCaptchaBundle** (see "OAuth2 server (removed)").
- Done: the broken OCTGN commands (`app:octgn`, `app:cards:octgn`). The OCTGN imports and exports
  are kept (see "OCTGN features").
- Done: **twig/extensions** (abandoned, none of its extensions was registered).
- **Dead code found by the tests, not removed yet**:
  - the `/deck/can_publish/{id}` route (`deck_publish`), pointing to the missing
    `SocialController::publishAction` (see "Website browsing");
  - `src/Resources/public/js/directimport.js`, loaded by no template (see "Deck
    workflow");
  - `app.suggestions-statistics.js` and `app.suggestions-heuristics.js`, loaded by no template
    (see "Console commands").
- Kept for now (decision taken): the admin card image upload (see "Admin area").

## 2. Decisions to take (keep or drop)

- **`/admin/stat_cards` and the `app:stats:precompute-cards` cron**: nothing in the repository
  consumes that JSON, probably an external report. Ask the maintainers / check the nginx logs
  (see "Card statistics").
- **User blocking**: FOSUserBundle 2 no longer enforces the `locked` column; `User` now
  overrides `isAccountNonLocked()` (and the expiry checks) so the admin "Block" button works
  again. Move this to a `UserChecker` when replacing FOSUser (see "Admin area").
- Done: **JSONP on the public API**, the callback is validated (see "Public API").
- **Card scraping commands**: `app:beorn:html` (`ScrapBeornCardDataCommand`, scrapes the Hall of
  Beorn HTML pages, still full of debug output), `app:beorn:json` and `app:download-images`
  (`app:cgdb:cards` was removed). The CSV import
  (`BeornJSONtoRingsDBcsv.py`, see "Admin area") seems to have replaced them; `app:beorn:scenario`
  is still used by the admin scenario import. Keep only what the maintainers still run.
- Decided: **`/api/doc` (NelmioApiDocBundle 2.x)** is kept. It is the public API documentation,
  generated from the `@ApiDoc` annotations of `ApiController` (8), and linked from the API
  introduction page (`/api/`, `Default/apiIntro.html.twig`). No test covers it. Porting it means
  NelmioApiDocBundle 4+, which is a rewrite (OpenAPI attributes).

## 3. Fixes cheaper to make now, with the tests

- **PHP 8 breakers**:
  - Fixed: `POST /deck/autosave` called `count()` on a decoded object (see "Deck workflow").
  - The deck contents are decoded with `(array) json_decode(...)` (objects inside) in the builder
    and in quest logs; the rewrite must keep the `{}` vs `[]` distinction of the empty deck guard
    (see "Deck workflow", "Quest logs").
- **SQL built by concatenation**: `StatController` (`month`, SQL injection, admin only),
  `listDecklistsByDateAction` (safe only thanks to the route requirement), the card force delete,
  the month of `CardStatsCalculator`. Small, safe fixes: use parameters.
- **To decide**: the `CoreExceptionListener` status code bug (every HTTP exception is a `500` for
  AJAX requests, see "Decklist comments"), set aside so far. Fixing it now, while the tests are
  there, shows every status that changes; it would have to be fixed anyway when the error
  handling is rewritten.
- Optional, functional bugs pinned by the tests: "Save and Publish" of fellowships never
  publishes (see "Fellowships"); the "deleted deck" branch of the quest log save reads the wrong
  field (see "Quest logs"); review comments are escaped twice (see "Card reviews").

## 4. Open test gaps

- Scenario import: waiting for a sample file (see "Admin area").
- The merging of reprints by `source_code()` in the card statistics (see "Card statistics").
- `/api/doc` (see above).

## 5. Left for during or after the migration

- **CSRF protection** is missing on almost every form and AJAX action, and several **GET routes
  write** (`/deck/new`, `/deck/clone`, `/deck/copy`, `/fellowship/publish`, the admin moderation
  actions, `/review/remove`). Symfony 7's forms and security make both easy; doing it now would
  mean doing it twice. Listed in each section of the reference.
- **Line endings of the text exports** (CRLF → LF) after the migration: see "After the
  migration".
- **Time in tests** (`ClockInterface`): see "Tests and time".
- **Static analysis** (`make phpstan`, level 8 of phpstan 2.2, see "Static analysis"): level 9,
  the nullable columns typed non-null; the analysis of the tests
  (`phpstan-phpunit`, with PHPUnit 9).

# Migration plan

## Strategy

Decided: step through the LTS versions (3.4 → 4.4 → 5.4 → 6.4 → 7.4), fixing the deprecations at
each step, and deploy each step to production (the detailed plan is in `UPGRADE_PATH.md`). PHP
is only upgraded where a step needs it, to limit the production upgrades: it stays 7.4 until
Symfony 5.4. FOSUserBundle is replaced (decided) with the Symfony 5.4 step, before PHP 8.4.

## Progress

### Symfony 3.4 (with PHP 7.4)

Symfony 3.4.49 (with the Symfony 4 directory structure since, see below), Twig 2,
FOSUserBundle 2.1, Doctrine ORM 2.7 / DBAL 2.13, DoctrineBundle 1.12,
doctrine-migrations-bundle 2.2 (see "Environment"), PHP 7.4 (the local stack, aligned with
production), phpstan 2.2, PhpSpreadsheet 1.30 instead of PHPExcel (see "Admin area"). The lock
is now a Composer 2 lock. Symfony 3.0 was skipped: 3.0.9 calls Twig's `getExtension('core')`,
which Twig 2 no longer has.

Code changes: `form_start()` / `form_end()` instead of `form_enctype()` (admin CRUD, FOSUser
templates), `assets.packages` instead of the removed `templating.helper.assets`,
`WebServerBundle` for `server:run` (dev / test), the `_configurator` route removed. Behaviour
changes pinned by the tests:

- The session listener makes every response of a request that used the session
  `max-age=0, must-revalidate, private`: the private API responses lose their
  `private, must-revalidate` / `no-cache` headers (see "Private API"). `Last-Modified` and the
  `304` answers are unchanged.
- `hide_user_not_found` also hides the account status errors since 3.4, so the login page could
  no longer show "Account is disabled." (and its confirmation email link). It is now `false`,
  and an unknown username still reads "Invalid credentials." through the
  `security.en.yml` translation (see "Removing FOSUserBundle").
- The date fields of the admin pack form have hidden (`sr-only`) Year / Month / Day labels.

The Composer scripts of `sensio/distribution-bundle` failed with Composer 2 (they pass a string
to `Process`): they were replaced by the commands themselves (`symfony-scripts`: parameters,
`cache:clear`, `assets:install`, `assetic:dump`), and the bundle was removed with its
environment check files (`web/config.php`, `app/check.php`, `app/SymfonyRequirements.php`).

### Symfony 4 directory structure (still on Symfony 3.4)

The Symfony 4 layout, adopted before the Symfony 4 step so that the upgrade itself only changes
the dependencies:

| Before | Now |
|---|---|
| `app/AppKernel.php`, `app/AppCache.php`, `app/autoload.php` | `src/Kernel.php` (`MicroKernelTrait`, bundles in `config/bundles.php`); no `AppCache` (it was unused) |
| `app/console` | `bin/console` |
| `web/`, `web/app.php` / `app_dev.php` | `public/`, `public/index.php` (one front controller, the environment comes from `APP_ENV`) |
| `app/config/config*.yml`, `security.yml`, `services.yml` | `config/packages/*.yaml` (`dev/`, `test/`, `prod/` for the environments), `config/services.yaml` (+ `services_prod.yaml`) |
| `app/config/parameters.yml` (+ `parameters_test.yml`) | environment variables, loaded by `config/bootstrap.php`: `.env` (committed, the defaults), `.env.dev` (committed, the local Docker stack), `.env.test` (committed, the tests), `.env.local` (per machine, not committed: the real values of a server) |
| `app/config/routing*.yml` | `config/routes/*.yaml`, importing `src/Resources/config/routing.yml` |
| `src/AppBundle/` (namespace `AppBundle\`) | `src/` (namespace `App\`); `AppBundle` and its `DependencyInjection/` are gone |
| `src/AppBundle/Resources/views/`, `app/Resources/*/views/` | `templates/` (bundle overrides in `templates/bundles/`) |
| `tests/AppBundle/` (namespace `Tests\AppBundle\`) | `tests/` (namespace `App\Tests\`) |
| `app/DoctrineMigrations/` (namespace `Application\Migrations`) | `src/Migrations/` (namespace `App\Migrations`); `migration_versions` stores the version numbers only, so nothing changes in the database |
| `var/logs/` | `var/log/` |

Details:

- **Environment variables** (`.env`): `DATABASE_*`, `MAILER_*`, `APP_SECRET`,
  `CACHE_EXPIRATION`, `EMAIL_SENDER_*`, `GAME_NAME`, `PUBLISHER_NAME`, `DRAGNCARDS_ROOM`,
  `NOINDEX`, `APP_ENV`. Each package config reads its own (`%env(MAILER_HOST)%` in
  `swiftmailer.yaml`...), not through parameters. The files follow the Symfony 4.2+ convention:
  the `.env*` files are committed (defaults and local credentials only), the `*.local` ones are
  not. Symfony 3.4's Dotenv has no `loadEnv()`: `config/bootstrap.php` does the same by hand. It
  loads `.env`, then `.env.local` (not in test, so that the tests do not depend on the
  machine), reads `APP_ENV` (so a server sets `APP_ENV=prod` in its `.env.local`), then loads
  `.env.<env>` and `.env.<env>.local`; each file wins over the previous ones, and a real
  environment variable wins over all of them (`deploy.sh` exports `APP_ENV=prod`,
  `phpunit.xml.dist` sets `APP_ENV=test`). As `.env.dev` is loaded after `.env.local`, a machine
  changes a value of `.env.dev` in `.env.dev.local`. The Docker stack keeps its own default
  for `CARD_IMAGES_DIR` (`docker-compose.yaml`, which reads neither `.env.local` nor
  `.env.dev`). The unused `website_name` and `google_*` parameters were dropped.
  **Once per server**: create `.env.local` with the values of the former
  `app/config/parameters.yml` (`deploy.sh` refuses to run without it).
- **Template names**: `'Dir/file.html.twig'` instead of `'AppBundle:Dir:file.html.twig'`,
  `'layout.html.twig'` instead of `'AppBundle::layout.html.twig'`, `'@FOSUser/...'` instead of
  `'FOSUserBundle::...'`. `User/remind-no-token.html.twig` extended a template of another project
  (`AgfaWebBundle::security.html.twig`): it now extends `@FOSUser/layout.html.twig`.
- **Controllers in the routes**: `'App\Controller\XController::yAction'` (and in the
  `forward()` calls) instead of `AppBundle:X:y`.
- **Doctrine**: the mappings are declared (`is_bundle: false`, `src/Resources/config/doctrine`,
  alias `App`, so the `App:Card` aliases of DQL still work until ORM 3).
- **Paths**: the services that built `%kernel.root_dir%/../web` get `$publicDir`
  (`%kernel.project_dir%/public`); `kernel.root_dir` is deprecated in 4.2.
- **Application assets**: `src/Resources/public/` is no longer installed by `assets:install` (there
  is no bundle any more). The URLs stay `/bundles/app/...` through the symlink
  `public/bundles/app` → `../../src/Resources/public`, created by a Composer script right after
  `assets:install` (which deletes every directory of `public/bundles/` that is not a bundle's)
  and by the Docker entrypoint. Moving these files to `public/` (and their URLs) is left for
  later.
- **Composer**: the `AppKernel` classmap, the `incenteev/composer-parameter-handler` script and
  package are gone. `symfony/symfony` is replaced by the components the application uses,
  each required explicitly in `^3.4` (the components it only gets through other packages too:
  without `symfony/symfony`, their constraints let Composer install 4.x or 5.x versions, a
  mix that the tests do not cover). Symfony Flex is installed, without applying its recipes to
  the existing configuration (`symfony.lock` lists every package as configured, so they are not
  applied again; a new package's recipe is still proposed). The Composer scripts are Flex
  `auto-scripts`. Kept from the recipes: the commented-out configuration files (they show what
  can be enabled), `bin/phpunit`, `DoctrineCacheBundle` (unused for now) and
  `config/packages/prod/doctrine.yaml`, which enables the Doctrine metadata, query and result
  caches in production and stops generating the proxies on the fly (`deploy.sh` warms the cache
  up, which writes them).
- **Configuration file names** (Flex convention): `phpunit.xml.dist`, `phpstan.neon.dist`,
  `tests/bootstrap.php` (formerly `phpunit.xml`, `phpstan.neon`, `phpunit.bootstrap.php`); a
  local `phpunit.xml` or `phpstan.neon` (ignored by git) overrides them.
- **phpstan**: the container is now `var/cache/test/srcTestDebugProjectContainer.xml` (the name
  comes from the kernel's directory in 3.4, `App_KernelTestDebugContainer.xml` in 4.x).

### Services and dependency injection

Needed before Symfony 4, where services are private and `Controller` / `ContainerAwareCommand`
are deprecated. Done by hand (no Rector):

- `services.yml` registers every class of the bundle as a service, identified by its class
  name, with autowiring and autoconfiguration. The services receive interfaces
  (`EntityManagerInterface`, `UrlGeneratorInterface`, `LoggerInterface`). The parameters are
  bound by name in `_defaults` (`$rootDir`, `$cacheDir`, `$cacheExpiration`, `$gameName`,
  `$publisherName`). The historical ids (`texts`, `decks`, `fellowship_manager`...) are only
  kept as aliases for the fixtures and the tests.
- The commands extend `Command` and receive their dependencies in their constructor.
- The controllers extend `AbstractController`: the services used by several methods and the
  parameters are injected in the constructor, the services used by one action are arguments of
  that action (`controller.service_arguments`). Left: `$this->get('session')` (one of the
  services `AbstractController` still provides, to replace with `$request->getSession()` before
  Symfony 6) and `getDoctrine()` (deprecated in Symfony 5.4).
- Left: the fixtures (`ContainerAwareInterface`, DoctrineFixturesBundle 2.x) and the tests
  still fetch services from the container by id.

## Dependencies

| Package | Status | Replacement / action |
|---|---|---|
| `friendsofsymfony/user-bundle` 2.0 | to be replaced (decided) | Symfony Security, see "Removing FOSUserBundle" |
| `symfony/assetic-bundle`, `leafo/scssphp`, `patchwork/jsqueeze` | abandoned, blocked Symfony 4 | done: replaced by `app:assets` and `scssphp/scssphp`, see "Front-end assets" (packages to remove) |
| `symfony/swiftmailer-bundle` 3.3 (SwiftMailer 6) | abandoned | Symfony Mailer (`new \Swift_Message()` in the comment notifications, FOSUser emails) |
| `liuggio/excelbundle` (PHPExcel) | done | replaced by PhpSpreadsheet (admin Excel export / import) |
| `sensio/framework-extra-bundle` | abandoned | native attributes (`#[Route]`, `#[IsGranted]`, `#[MapEntity]`) |
| `symfony/symfony` | done | replaced by the components, required one by one in `^3.4` (see "Symfony 4 directory structure") |
| `incenteev/composer-parameter-handler` | done | removed: `.env` files (see "Symfony 4 directory structure"); Symfony Flex installed without its recipes (`sensio/distribution-bundle` and `sensio/generator-bundle` removed; MakerBundle if code generation is needed) |
| `nelmio/api-doc-bundle` 2.13 | 2.x supports Symfony 4, not 5; major rewrite in 4.x | see roadmap ("`/api/doc`"); its commands (`api:doc:dump`, unused) are auto-registered, so they are gone in Symfony 4.0 |
| `friendsofsymfony/jsrouting-bundle` 2.8 | maintained (3.x) | kept to the end; `routes_to_expose: ['.*']` exposes every route, admin included: restrict it |
| `gedmo/doctrine-extensions` 2.x | maintained (3.x) | upgrade; the timestampable listener is declared by hand (`doctrine_extensions.yml`), or use `stof/doctrine-extensions-bundle` |
| `doctrine/orm` 2.x, `doctrine/dbal` 2.x | | ORM 3 / DBAL 4; the custom DQL functions `replace` and `power` (see "Card search") |
| `ezyang/htmlpurifier`, `erusev/parsedown` | maintained | upgrade |
| `phpstan/phpstan` 2.2 (dev) | maintained | `phpstan-symfony` and `phpstan-doctrine` installed; `phpstan-phpunit` with PHPUnit 9 (see "Static analysis") |

## Front-end assets

Done: Assetic (abandoned, its last version supports Symfony 2 and 3 only, so it blocked Symfony 4)
is gone. AssetMapper, the current replacement, needs Symfony 6.3; Webpack Encore needs Node. The
assets are plain files, built without Node; not covered by the tests (they check the visible text
of the pages, not the assets): after a change, check the pages by hand (no 404, no JavaScript
error, same look).

- `app:assets` (`BuildAssetsCommand`) builds the files loaded by every page, listed in order in
  `App\Asset\AssetBundles`, in every environment, without minification (gzip does most of
  it): `public/js/extra.js` (the libraries of `Resources/public/cdn/js/`), `public/js/app.js` (the
  application), concatenated; `public/css/app.css`, the `.css` concatenated as they are and the
  `.scss` compiled by `scssphp/scssphp` 1.x (2.x needs PHP 8.1), their relative `url(...)`
  rewritten for `public/css/` (what Assetic's `cssrewrite` did). Compared with the last Assetic
  build: the same rules (Assetic compressed everything and shortened the colours; one selector
  list comes out in another order, same rule).
- It runs as a Composer script (so on each deployment) and in the dev entrypoint; after a change
  of one of these files: `make assets`. The script of each page (`ui.*.js`) is a plain
  `<script src="{{ asset('bundles/app/js/...') }}">`. The inline script of the layout stays
  between the two JavaScript files: it creates the global `app` that the `app.*.js` files extend.
- `App\Asset\ContentHashVersionStrategy` adds `?v=<hash of the content>` to the URLs of the
  `.js` and `.css` files (Assetic's cache busting did it), and leaves the other assets alone.
- The libraries are old (jQuery 2, Bootstrap 3, Highcharts 4, moment 2.12...): upgrading them is
  another step.

## Porting the test suite

Most tests go through HTTP and compare snapshots, so they survive the migration. Coupled to the
current stack:

- the `KernelTestCase` tests (managers, commands, card statistics): service ids
  (`static::$kernel->getContainer()->get('fellowship_manager')`, `'doctrine'`), public until
  Symfony 4.1 brings `test.service_container`; `getRootDir()`;
- PHPUnit 6.5 APIs: `assertContains()` on strings, `assertRegExp()`. PHP 7.4 allows up to
  PHPUnit 9.6. The test methods, `setUp()` and `tearDown()` already declare `: void` (required from
  PHPUnit 8);
- the fixtures (`DoctrineFixturesBundle` 2.x) and the `make test-fixtures` loading.

To plan with the strategy: with LTS steps, the suite is upgraded as PHP goes up; with a new
skeleton, it is ported first, then run against the new application.

## Static analysis

`make phpstan` runs phpstan 2.2 at level 8 on `src/` (configuration in `phpstan.neon.dist`; the tests
are no longer analysed), with the official extensions (loaded by `phpstan/extension-installer`):

- `phpstan-symfony`: the service types, read from the container dumped in `var/cache/test`
  (hence the `cache:warmup` of `make phpstan`), and the console helpers
  (`src/PHPStan/console-application.php`);
- `phpstan-doctrine`: the entity metadata, from the YAML mappings through the entity manager of the
  test environment (`src/PHPStan/object-manager.php`): the repositories, the fields,
  the collections, the DQL. It needs the class names: `getRepository(Card::class)`, the
  `'AppBundle:Card'` aliases were replaced (they are gone in ORM 3; the aliases inside DQL strings
  remain, still valid in ORM 2.7). phpstan's result cache does not know the mappings: after
  changing one, `vendor/bin/phpstan clear-result-cache`.

Our own extensions in `src/PHPStan/` are down to two: the Doctrine registry
(`getManager()` / `getConnection()` return the ORM entity manager / the DBAL connection, which
phpstan-doctrine does not say) and the logged in user (`getUser()` is an `App\Entity\User`).
The ones that only served the analysis of the tests were removed with it: the PHPUnit assertions
narrowing types, the non-null response / request / container of the test client,
`HeaderBag::get()`, the PHPUnit stub and bootstrap file. `phpstan-phpunit` is not installed (its
latest version needs phpstan 2.3 and conflicts with PHPUnit < 7): to consider with PHPUnit 9, if
the tests are analysed again.

Found by `phpstan-doctrine` and fixed:
- the required associations were nullable: 32 mappings declared `nullable: false` on the
  association, where Doctrine ignores it, instead of on the join column (migration
  `Version20260929215538`, see "Environment");
- `Decklist::addFellowship()` / `removeFellowship()` expected a `FellowshipDeck` instead of a
  `FellowshipDecklist` (never called);
- the dead `QuestLogManager::findQuestLogsByRecentDiscussion()` (see "Removed dead code").

Columns nullable in the database but typed non-null in the entities (`doctrine.columnType`, no
longer ignored: nothing is ignored in `phpstan.neon.dist`). For the reference data, the bootstrap is the
production data, so each column was checked: in `Card`, `traits`, `text`, `flavor`, `cost` (a
string: `X`, `-`...) and the stats are nullable (a hero has no cost, an ally no threat...), so the
properties became nullable; `deck_limit` never is: `NOT NULL`, 3 by default (migration
`Version20260930090741`; `setDeckLimit(null)`, from an empty field of the admin form or of a CSV
import, stores 3). In `CardPrinting`, no column can become `NOT NULL`: `illustrator` and
`octgnid` are missing for some printings (70, 74), and the overrides (`traits` ... `quest`) are
`NULL` for all of them (nullable by design, "empty = the value of the card"). `Pack.dateRelease`
has no `NULL` in the reference data, but `NULL` means an unreleased pack: kept nullable.
`Sphere.octgnid` (`NULL` for the 7 spheres, used by nothing) was dropped (migration
`Version20260930102154`). The user content, whose production data is unknown, keeps its nullable
columns and got nullable properties: `User.resume`, `color`, `ownedPacks` (`NULL` for the
accounts that never saved their profile or collection, read like `''` everywhere), the
descriptions of decks, decklists, fellowships and quest logs, `dateLastComment`, `datePublish`,
`Deck.tags` / `problem`, `Deckchange.version`, `Decklist.freezeComments`, `Questlog.score`,
`QuestlogDeck.player`. The callers needed two `(string)` casts, same behaviour in PHP 7.4: the
traits of the heroes for Folco (`SlotCollectionDecorator`), the printings' octgnid in the CSV
import.

Left for later: level 9 (1136 errors with phpstan 1.4, all about `mixed`: request
parameters, query results, the `mixed` parameters of the level 6 docblocks), cheaper on the
rewritten code.

The value types of the arrays are checked (`missingType.iterableValue`, 51 docblocks of `src/`):
precise shapes where the structure is small and stable (the import parsers, the page links of
the managers, the deck contents), `array<string, mixed>` for the large ones (the card infos, the
SQL rows). Found on the way: the archive import tested a `content` its parsers always return (one
deck per file, even empty: unchanged).

The generic types are checked too (`missingType.generics`): the collections of the entities
(`Collection<int, Deckslot>`...) and their getters, the paginators of the managers
(`Paginator<Decklist>`), the forms (`AbstractType<Card>`, `FormInterface<Sphere>`), and
`SlotCollectionInterface<T of SlotInterface>` (the slots of a deck, of a decklist...; the code
that accepts any of them uses `SlotCollectionInterface<covariant SlotInterface>`). Once the
elements were typed, phpstan found defensive code that could never run, removed: null checks on
the sphere / type of a card, the author of a comment, the deck of a fellowship (all `NOT NULL`),
the items of a paginator; `$cycle->getPacks()[0]` replaced by `first()` (seven places). And the
guards of `SlotCollectionDecorator` against slots without a card (commit `4a245da8`, for the
quest log snapshots): a slot cannot have no card (`setCard()` takes a `Card`), the actual crash
was in `Decks::setSlots()`, fixed (see "Quest logs").

## Environment

- **Production database**: the tests run on MySQL 8.0, the version of production, with the
  default `sql_mode`. Production runs without `ONLY_FULL_GROUP_BY` (checked on 2026-09-29:
  `STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION`),
  the other modes are the same. The tests stay stricter on purpose: a query accepted with
  `ONLY_FULL_GROUP_BY` works the same without it, and the mode catches the ambiguous
  `GROUP BY`s, which return an arbitrary row of the group in production (several queries were
  fixed for it, see "Card statistics", "Lists and search managers").
- **Composer**: since the Symfony 3.4 step, the lock is updated with Composer 2 and production
  runs a normal `composer install` (decided on 2026-09-28), with `APP_ENV=prod` (read by
  the `bin/console` calls of the Composer scripts).
- **Deployment**: `./deploy.sh`, run on the server from the checkout to update (it works on its
  own directory, so the same script serves the production and test checkouts; the previous
  script is kept as `deploy_old.sh`). Steps:
  0. checks, before any change: the card images (see below); `.env.local`; Composer (>= 2.2); fetches the
     upstream branch, checks that it can be fast-forwarded without overwriting local changes
     (`git read-tree -mun`, a dry run of the checkout), and checks the PHP version and
     extensions its `composer.lock` requires (`composer check-platform-reqs --lock --no-dev`,
     on a copy of its `composer.json` / `composer.lock`);
  1. switches to maintenance mode: creates `maintenance.flag` at the root of the checkout, then
     waits 10 seconds for the requests in progress. While the flag exists, `public/index.php`
     answers `503` with `public/maintenance.html` before loading anything, so
     nothing writes to the database or the cache during the update;
  2. snapshots the database (`mysqldump --single-transaction --no-tablespaces`, connection from
     the `DATABASE_*` variables) to `$SNAPSHOT_DIR` (default `~/db-snapshots`, mode 700: the snapshots hold
     the users' data). The snapshot has no stored function (created by root, the application user
     cannot dump it): to restore, load it then `function-source-code.sql` as root;
  3. fast-forwards the branch to the commit checked in step 0 (`git merge --ff-only`; with
     `SKIP_PULL=1`, no fetch and the current commit is redeployed);
  4. backs up `vendor/` to `vendor.bak/` (replacing the previous backup), removes the prod cache (renamed first) and runs `composer install --no-dev
     --optimize-autoloader --no-interaction`, whose scripts clear the cache and build the assets.
     The prod kernel uses its cached container without checking it: without the removal, the
     `cache:clear` would boot the container of the previous code. Then links the card images
     and warms the cache up (`cache:warmup`: the scripts clear it with `--no-warmup`, and the
     prod Doctrine proxies are only written by the warmup);
  5. applies the Doctrine migrations (`doctrine:migrations:migrate --allow-no-migration`);
  6. refreshes the ACLs of `var/cache` and `var/log` (`setfacl`, best-effort);
  7. leaves maintenance mode.

  If a step fails, the site stays in maintenance mode and the script prints how to roll back
  (previous commit, `vendor.bak/`, the database snapshot); `rm maintenance.flag` once fixed.

  `MAINTENANCE=0` keeps the site up (trivial redeploys). **First deployment**: see "First
  production deployment" below.
- **Card images** (about 832 MB, not in git): they live outside the checkout, in
  `$CARD_IMAGES_DIR` (to set in the environment of the deploying user, e.g. in `~/.profile`; the
  three checkouts can share it), served as `/bundles/cards/<code>.png` through the symlink
  `public/bundles/cards`, that `deploy.sh` recreates. Since Symfony 3.4, `assets:install` (a
  Composer script) deletes every directory of `public/bundles/` that is not a bundle's: a real
  `public/bundles/cards` directory would be lost, a symlink is only unlinked (its target is kept).
  `deploy.sh` refuses to run while `public/bundles/cards` (or the former `web/bundles/cards`) is a directory. Once per server, before
  the first deployment: `mv web/bundles/cards <dir>` and `export CARD_IMAGES_DIR=<dir>`. The web
  server must follow symlinks (Apache in production: `FollowSymLinks` or
  `SymLinksIfOwnerMatch`). Same trap locally: link `public/bundles/cards` to the images, never copy
  them there.
- **Schema changes**: from now on they go through `doctrine/doctrine-migrations-bundle` (2.2), in
  `src/Migrations/` (namespace `App\Migrations`, table `migration_versions`).
  `ringsdb_bootstrap.sql` stays the production schema before the first Doctrine migration, so
  `make fixtures` / `make test-fixtures` run `doctrine:migrations:migrate` after loading it; in
  production, `deploy.sh` runs it (see "Deployment"). The older hand-written scripts of `migrations/` are
  already applied in production (and part of the bootstrap).
  - `Version20260929134447`: aligns `user` with the FOSUserBundle 2 mappings (`username`,
    `email` and their canonical versions shortened to 180 characters, nullable `salt`, unique
    `confirmation_token`) and `user_custom_pack_card.quantity` (`TINYINT UNSIGNED` →
    `SMALLINT UNSIGNED`: DBAL 2 maps `TINYINT` to a boolean). It fails if a value is longer
    than 180 characters or if a confirmation token is duplicated (queries in its docblock):
    checked on production on 2026-09-29, the values are under 50 characters and no confirmation
    token is duplicated.
  - `Version20260929135555`: drops the `oauth2_*` tables (see "OAuth2 server (removed)").
  - `Version20260929215538`: makes the foreign keys of the 32 required associations `NOT NULL`
    (see "Static analysis"). It fails if one of them contains `NULL`: run the query of its
    docblock on production first (every count must be 0, or `NULL` for an empty table).
  - `Version20260930090741`: makes `card.deck_limit` `NOT NULL`, 3 by default (see "Static
    analysis"). No card of the reference data has a `NULL` deck limit; the query of its docblock
    checks it on production.
  - `Version20260930102154`: drops the unused `sphere.octgnid` (see "OCTGN features").
  - `stat_cards_cache` has no entity (filled by SQL, see "Card statistics"): the
    `schema_filter` of the DBAL connection hides it from Doctrine, which would drop it
    otherwise. Any other table without an entity must be added to that filter.

## First production deployment

The first deployment of this branch changes, at once, the Symfony version (2.8 → 3.4), the
directory structure (`web/` → `public/`, `app/console` → `bin/console`, `parameters.yml` →
`.env` files), the dependency installation (`composer install`) and the database schema (the Doctrine
migrations). To do once per checkout: on the test checkout first, as a rehearsal, then on
production. The site is down (maintenance page) during step 6 only, a few minutes (mostly
`composer install` and the migrations).

### 1. Beforehand (the site stays up)

1. **Read the production values** of `app/config/parameters.yml` (not in git, it stays in
   place after the pull), and **create `.env.local`** at the root of the checkout (ignored by
   git, so the pull neither overwrites nor conflicts with it; the current code does not read
   it). The committed `.env` holds the defaults (see its comments): `.env.local` overrides
   them, so set every variable below, even when the value is the default:

   | `parameters.yml` | `.env.local` |
   |---|---|
   | — | `APP_ENV=prod` (**required**: Apache does not see the `APP_ENV` exported by `deploy.sh`; without it the site runs in `dev`, with the debug mode and the profiler) |
   | `database_host`, `database_port`, `database_name`, `database_user`, `database_password` | `DATABASE_HOST`, `DATABASE_PORT` (`3306` if `~`), `DATABASE_NAME`, `DATABASE_USER`, `DATABASE_PASSWORD` |
   | `mailer_transport`, `mailer_encryption`, `mailer_auth_mode`, `mailer_host`, `mailer_user`, `mailer_password` | `MAILER_TRANSPORT`, `MAILER_ENCRYPTION`, `MAILER_AUTH_MODE`, `MAILER_HOST`, `MAILER_USER`, `MAILER_PASSWORD` |
   | `secret` | `APP_SECRET` (**the same value**: it signs the remember-me cookies, a new one logs everybody out) |
   | `cache_expiration` | `CACHE_EXPIRATION` |
   | `email_sender_address`, `email_sender_name` | `EMAIL_SENDER_ADDRESS`, `EMAIL_SENDER_NAME` |
   | `game_name`, `publisher_name` | `GAME_NAME`, `PUBLISHER_NAME` |
   | `dragncards_room` | `DRAGNCARDS_ROOM` |
   | `noindex` | `NOINDEX` (`false` in production, `true` on the test checkout) |
   | `website_name`, `google_*` | — (unused, dropped) |

   Quote the values containing spaces or `#` (`GAME_NAME="LotR LCG"`); `~` becomes an empty
   value. `chmod 600 .env.local` (the web server reads it through its ACL, like the rest of the
   checkout). There must be no `.env.prod` or `.env.prod.local` with other values. A variable
   of the real environment wins over the `.env` files: check that none of these
   names is set in the Apache environment (`SetEnv`) with another value.
2. **Move the card images out of the checkout** (they would be deleted by `assets:install`,
   see "Card images"), and keep the current site working with a symlink:
   `mv web/bundles/cards /var/www/card-images && ln -s /var/www/card-images web/bundles/cards`,
   then `export CARD_IMAGES_DIR=/var/www/card-images` in `~/.profile` of the deploying user.
   `deploy.sh` refuses to run while `web/bundles/cards` or `public/bundles/cards` is a real
   directory.
3. **Prepare the Apache change** (it needs the rights on the Apache configuration, to prepare
   with whoever has them): in the virtual host, `DocumentRoot` and the `<Directory>` block move
   from `…/web` to `…/public`, with `AllowOverride All` (or at least `FileInfo`, for the rewrite
   rules of `public/.htaccess`) and `FollowSymLinks` or `SymLinksIfOwnerMatch` (the
   `bundles/*` symlinks). `public/.htaccess` routes everything to `index.php` and redirects the
   old `/app.php/...` URLs to `/...`. Check that `mod_rewrite` is enabled. Prepare it without
   reloading Apache yet (`apachectl configtest` after the change).
4. **Checks**: `composer --version` (>= 2.2), `php -v` (7.4) and the PHP extensions
   (`deploy.sh` checks them too, before any change), free disk space for the database snapshot
   and `vendor.bak/`.
5. **List the crontab** (`crontab -l`): every `app/console` becomes `bin/console`
   (`app:stats:precompute-cards`, `app:suggestions`...), to change in step 6. A cron that runs
   during the deployment would fail: comment them out for the duration.

### 2. The deployment

6. Pull, switch to maintenance mode and to the new document root, then deploy. The steps
   follow each other without delay: between the pull and the Apache reload, `web/` has no front
   controller any more (the requests get a 404).
   ```bash
   git rev-parse HEAD > ~/pre-deploy-commit   # the commit to roll back to
   git pull --ff-only
   touch maintenance.flag            # honoured by public/index.php
   sudo apachectl graceful           # (or the equivalent) the vhost change of step 3
   ./deploy.sh                       # finds the checkout up to date, deploys the current commit
   ```
   Do not run the checkout's `deploy.sh` before the pull: it is the new one only after it (the
   old script would clear the cache with the new code and the old `vendor/`). As the pull is
   done by hand, the "previous commit" that `deploy.sh` prints on failure is already the new
   one: roll back to the commit saved in `~/pre-deploy-commit`.
   `deploy.sh` snapshots the database, runs `composer install` (which clears the cache, links
   `public/bundles/*` and builds `public/js`, `public/css`), links the card images, applies the
   Doctrine migrations and leaves maintenance mode. If one of its checks or steps fails, the site
   stays in maintenance mode and the script prints how to roll back (see "Rollback" below).
7. Regenerate the files that were in `web/`: `php bin/console app:suggestions`
   (`public/suggestions.json`, loaded by the deck builder).
8. Update the crontab (step 5) and uncomment it.

### 3. Checks

9. The home page, a card page (image shown: the symlink), a deck in the deck builder
   (JavaScript, suggestions), login (https), `/api/public/cards/`. `curl -I` of a page: no
   `X-Debug-Token` header (it would mean `APP_ENV` is not `prod`). An old URL
   (`/app.php/decklists`) redirects to `/decklists`.
10. Send an email (password reset request on a test account): the mailer settings.
11. `php bin/console doctrine:migrations:status`: no new migration; `var/log/prod.log`: no error.

### 4. Cleaning up (once the site is checked, e.g. the next day)

12. The leftovers of the old layout, not in git so not removed by the pull: `web/`
    (`web/bundles/`, `web/js/`, `web/css/`, `web/suggestions.json`, the `web/bundles/cards`
    symlink), `app/` (`app/config/parameters.yml`, once `.env.local` is known to be right: keep a copy
    outside the checkout, owner only, it holds the passwords), `var/logs/` (the logs are in
    `var/log/` now), `deploy_old.sh`.
13. `vendor.bak/` and the database snapshot can stay until the next deployment (which replaces
    `vendor.bak/`); the snapshots of `$SNAPSHOT_DIR` are not rotated.

### Rollback

While in maintenance mode (the users' data has not changed since the snapshot):

1. `git reset --hard $(cat ~/pre-deploy-commit)`, `rm -rf vendor && mv vendor.bak vendor` if
   `composer install` ran (`vendor.bak/` is then the old `vendor/`),
   `rm -rf var/cache/prod`;
2. if the migrations ran: restore the snapshot (`gunzip -c <snapshot> | mysql …`), then
   `function-source-code.sql` as root;
3. put the Apache `DocumentRoot` back on `web/` and reload Apache;
4. `rm maintenance.flag`. The old code reads `app/config/parameters.yml` (kept until step 12)
   and the card images through the `web/bundles/cards` symlink of step 2.

# Reference

## Removing FOSUserBundle / rewriting the Security layer

Covered by `tests/Controller/SecurityControllerTest.php` (registration, email
confirmation, login, remember-me, logout).

### Password hashing

Current algorithm (`security.yml` → `encoders: FOS\UserBundle\Model\UserInterface: sha512`):
`MessageDigestPasswordEncoder` with its default settings, i.e. SHA-512, 5000 iterations,
base64 output (88 characters).

```php
$salted = $password . '{' . $salt . '}';
$digest = hash('sha512', $salted, true);
for ($i = 1; $i < 5000; $i++) {
    $digest = hash('sha512', $digest . $salted, true);
}
$hash = base64_encode($digest);
```

- The salt is per user and stored in the database (`salt` column). FOSUser 2.0 generates it
  with `rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=')` (43 characters).
  Existing salts are reused as is: how they were generated does not matter for the migration.
- A single encoder is configured for the whole user class, so every account that can log in
  today has a hash in this format. No extra check on production data is needed.

In Symfony 7.4:

- Declare an identical legacy hasher:
  ```yaml
  security:
      password_hashers:
          legacy_sha512:
              algorithm: sha512
              encode_as_base64: true
              iterations: 5000
          App\Entity\User:
              algorithm: auto
              migrate_from:
                  - legacy_sha512
  ```
- The `User` entity must implement `LegacyPasswordAuthenticatedUserInterface` (to expose
  `getSalt()`). **Otherwise the salt is not passed to the hasher and every login fails.**
- Implement `PasswordUpgraderInterface` on the user provider (or repository) so passwords are
  re-hashed with bcrypt/argon as users log in. New hashes have no separate salt (`salt` column
  set to `null`).

### Behaviour to reproduce without FOSUser

- **Login by username OR email**: the current provider is
  `fos_user.user_provider.username_email`. The new user provider must accept both (covered by
  `testLoginWithUsername` / `testLoginWithEmail`).
- **Registration with email confirmation** (`fos_user.registration.confirmation.enabled:
  true`): account created disabled with a `confirmationToken`, email containing the
  `/register/confirm/{token}` link, activation + automatic login, single-use token.
- **Login refused for an unconfirmed account** with the `Account is disabled.` message: the
  login template relies on this `messageKey` to display the `/user/remind/{username}` link.
  In Symfony 7, use a `UserChecker` and keep a recognisable message (or update the template
  and the test).
- **Registration validation**: unique username and email, valid email format, username of at
  least 2 characters, password confirmation, CSRF protection.
- **Remember-me**: 365-day lifetime, `REMEMBERME` cookie.
- **Redirect after login** to the originally requested page, otherwise `index`.
- **FOSUser routes still in use**: `/login`, `/login_check`, `/logout`, `/register/*`,
  `/resetting/*`, `/profile/*` (see `config/routes/routes.yaml`). Password reset (`/resetting`)
  is not covered by the tests yet.

### Configuration issues found

- `hide_user_not_found: false` (`security.yml`, since Symfony 3.4, see "Progress") keeps the
  "Account is disabled." message; the translation of "Username could not be found." into
  "Invalid credentials." (`app/Resources/translations/security.en.yml`) keeps unknown usernames
  indistinguishable from wrong passwords. Both are to reproduce in the new login.
- `config.yml` declares `fos_user.firewall_name: main`, but the firewall is named `default`.
  It works today (automatic login after confirmation is tested), but it should be fixed in the
  new configuration.
- Removed: the FOSUserBundle overrides named after FOSUser 1.x templates, which FOSUser 2 does not
  load (it renders `check_email`, `change_password`...): `Registration/checkEmail.html.twig`,
  `Resetting/checkEmail.html.twig`, `Resetting/passwordAlreadyRequested.html.twig` and
  `ChangePassword/changePassword*.html.twig`. The pages already used FOSUser's templates.

## Public API (`/api/public/*`)

Covered by `tests/Controller/ApiControllerTest.php`. Response bodies are compared
to snapshots in `tests/Resources/snapshots/api/`: strict on structure, key order,
value types and `{}` vs `[]`, but not on whitespace or JSON escaping. Status codes and the
`Content-Type`, `Cache-Control`, `Access-Control-Allow-Origin` and `Last-Modified` headers are
checked too, as well as `304 Not Modified` on `If-Modified-Since` and JSONP (`?jsonp=callback`).

Regenerate the snapshots only on purpose, and review the diff:
`docker compose exec -e UPDATE_SNAPSHOTS=1 -u www-data symfony php bin/simple-phpunit`

The private API (`/api/private`) is kept: the site's own JavaScript uses its 4 routes,
authenticated by the regular session cookie (`api_private_load_deck`, `api_private_my_decks`,
`api_private_user_decks` for the builder's multi-deck mode and the deck picker of fellowships and
quest logs, in `app.deck.js` / `app.deck_selection.js`; `api_private_custom_packs` in
`app.ui.js`). Covered by `tests/Controller/ApiPrivateControllerTest.php` (see
"Private API" below).

The OAuth2 API (`/api/oauth2`) has been removed: see "OAuth2 server (removed)" below.

### Current behaviour pinned by the tests (quirks to keep or fix on purpose)

- `/cards/{pack_code}` is case-insensitive (`core` and `Core` both work).
- `/cards/{pack_code}.xml|xls|xlsx` returns `200` with the plain text body
  `<format> format not supported. Only json is supported.` (`text/xml` for xml, `text/html`
  for xls/xlsx). `/card/{code}.xml` is a `404` (route requirement).
- Fixed: `/cards/search/{q}` ignored the `jsonp` parameter (the action tested `isset($jsonp)` but
  never read it from the request). It now supports JSONP like the other endpoints.
- Fixed: the JSONP callback was echoed as is into the `application/javascript` response (XSS).
  `ApiController::setJsonContent()` validates it with `JsonResponse::setCallback()` (a JavaScript
  identifier, dots and brackets allowed, no reserved word): an invalid one is a `400`, an empty
  one is ignored (plain JSON). The script is now `/**/callback(json);` (the comment prefix
  protects against content sniffing); the `Content-Type` stays `application/javascript`.
- `/cards/` `Last-Modified` is the most recent `dateUpdate` of the cards **and** of their
  printings.
- `/custom-packs/published` and `/user/info` are not in `ApiController`: they return a
  `JsonResponse` with `Cache-Control: no-cache`/`private` and no CORS header.
- `/user/info` returns `null` for anonymous users.
- Error bodies (404) are not checked: they are the Symfony debug output in the test env.

### To look at during the migration

- `listDecklistsByDateAction` builds its DQL by string concatenation (`LIKE '$date%'`); it is
  only safe because of the route requirement `\d\d\d\d-\d\d-\d\d`. Use a parameter.
- `/custom-packs/published` is tested with a single published pack (`LoadCustomPackData`).
- The `/cards/` snapshot is ~2 MB (1315 cards).

## Website browsing (read-only)

Covered by `tests/Controller/WebsiteBrowsingTest.php`. HTML pages are compared to
text snapshots in `tests/Resources/snapshots/pages/` (visible text only, one line
per text node, no scripts/styles): strict on displayed content, not on markup or attributes.
Downloads are compared byte for byte; zip archives entry by entry.

### Current behaviour pinned by the tests

- `/questlog/view/{id}/{name}` redirects anonymous visitors to the login page, even for public
  quest logs: the `^/questlog/` access rule has no `view` exception (unlike `^/fellowship/view/`
  and `^/deck/view/`). Probably unintended.
- `/myquestlogs` and a private `/deck/view/{id}` are not covered by `access_control`: the
  controllers answer `403` instead of redirecting to the login page.
- `/decklists/mine` and `/decklists/favorites` are reachable anonymously (empty lists).

### To look at during the migration

- Some GET routes write to the database: `/deck/new` (creates a deck), `/deck/clone/{id}`,
  `/fellowship/publish/{id}`. They should become POST (with CSRF protection).
- `/deck/can_publish/{id}` (`deck_publish`) points to `SocialController::publishAction`, which
  does not exist (500). Dead route to remove.
- Fixed: `Texts::slugify()` relied on catching an iconv error when `//TRANSLIT` is not
  supported (musl, i.e. the Alpine Docker image), but the exception class differs between
  Symfony's and PHPUnit's error handlers. It now checks iconv's return value. With musl, accents
  are dropped from file names instead of transliterated (`Dáin` → `Din`).
- Fixed: zip exports used `tempnam("tmp", "zip")`, relative to the process working directory
  (`web/tmp` under the web server). They now use `%kernel.cache_dir%` and fail explicitly if
  the temporary file cannot be created.
- `slugify()` starts with `preg_replace('[^\w\-]', '-', ...)`: the brackets are taken as regex
  delimiters, so this line does not do what it seems to. Harmless, but worth cleaning up.

## Deck workflow (create / edit / publish)

Covered by `tests/Controller/DeckWorkflowTest.php`. Forms are submitted like the
browser does (the builder's JavaScript serializes the deck as JSON into the hidden `content`
field). Everything the tests create is deleted in `tearDown()`.

### Current behaviour pinned by the tests

- `GET /deck/new` creates an empty `New Deck` (problem `too_few_heroes`, version 0.0) and
  redirects to the builder.
- Each save increments the minor version and records a `deckchange` entry
  `[main added, main removed, side added, side removed]`. Quantities above the deck limit are
  capped silently.
- Publishing creates a decklist at version `<major+1>.0` with a canonical name
  `<slug>-<version>`; the deck moves on to `<major+1>.1`.
- An invalid deck cannot be published: the publish form redirects to the deck page with a
  flash error.
- Another user's deck cannot be edited, saved, or published (`403`).
- Deck comparison (`/deck/compare/{deck1}/{deck2}`, `DeckCompareTest`): cards in common with
  the minimum quantity, then what is left in each deck, for heroes, draw deck and sideboard;
  the decks are not modified; another user's decks require them to share their decks.
  Rewritten: `Diff::getSlotsDiff()` subtracted the common cards from the slots themselves (the
  page then read the decks for what was left), so it detached them first (`EntityManager::detach()`,
  removed in ORM 3). `Diff::compareSlots()` changes nothing: it returns the common cards and what
  is left in each deck, as `{card, quantity}` arrays that the template shows.
- `GET /deck/copy/{decklist_id}` copies a decklist into a new deck (version 0.1) whose parent is
  the decklist; publishing that deck creates a decklist whose predecessor is the original one
  ("Derived from" / "Inspiration for").

### To look at during the migration

- Empty decks, production behaviour kept on purpose: the "Cannot import an empty deck" guard
  of `/deck/save` (422 "Cannot save an empty deck." on `/deck/save-ajax`) only rejects a
  `content` whose `main` is missing or decodes to an empty array. The builder sends
  `{"main": {}, ...}`, decoded as a `stdClass` that is never `empty()`, so it can save an empty
  deck; a file import without any card sends `{"main": [], ...}` and is refused. Keep this
  distinction when rewriting the decoding (e.g. with `json_decode(..., true)` both would look
  the same).
- `QuestLogController` decodes deck contents the same way (`(array) json_decode(...)`), and
  refuses quest logs with an empty deck.
- Fixed: text import with a pack name (`1x Aragorn (Core Set)`, the format of the text export)
  crashed with "Unrecognized field: pack": `BuilderController::parseTextImport()` still queried
  `Card.pack`, removed by the card printings refactor. It now looks for a card with a printing
  in that pack. Covered by an export → import round trip of the fixture decks.
- `src/Resources/public/js/directimport.js` is not loaded by any template (the import
  page uses `ui.deckimport.js`). Dead file.
- `/deck/save` and `/decklist/create` have no CSRF protection.
- Deck tags (`/tag/add`, `/tag/remove`, `/tag/clear`, `TagControllerTest`): fixed, `/tag/` had no
  `access_control` rule and the controller calls `getUser()->getId()` without checking the user,
  so an anonymous request on an existing deck crashed (`500`); `^/tag/` now requires `ROLE_USER`
  (`403` JSON for AJAX, redirect to the login page otherwise). Fixed too: empty tags (from an
  empty or badly spaced tag string, or from the page splitting the typed text on spaces) were
  stored (`" gondor"`); tags are now normalized by `Decks::normalizeTags()` (distinct, non-empty),
  in `TagController` and in `Decks::saveDeck()`, where whitespace-only tags now fall back to the
  heroes' spheres like empty ones. Existing badly spaced tags are cleaned up on the next change.
  The JSON answer is sent as `text/html`. Other users' and unknown decks are skipped silently.
- `/deck/copy/{decklist_id}` writes to the database on GET (see also `/deck/new`).
- Clone, delete, delete list, autosave (`DeckManagementTest`):
  - `GET /deck/clone/{id}` (a GET that writes) copies a deck, own or shared, for the current user
    ("<name> (clone)", same parent decklist, version 0.1).
  - `POST /deck/delete` refuses a deck that belongs to a fellowship (flash error); decklists
    published from the deck are detached from it. BUG-ish: `POST /deck/delete_list` has no such
    check: the deck is deleted and silently removed from its fellowship (cascade remove on
    `Deck.fellowships`, the fellowship keeps its `nb_decks`).
  - `POST /deck/autosave` stores the builder's diff as an unsaved `deckchange`, replaced by a
    saved one on the next save. Fixed: the diff was decoded as objects and tested with
    `count()`, always 1 for an object (an empty diff still created an entry; a warning since
    PHP 7.2, a `TypeError` in PHP 8), and a 2-part diff with empty parts read undefined offsets.
    It is now decoded as arrays: an empty diff, in 4 or 2 parts, creates no entry.
  - Unknown deck, another user's deck, wrong diff: HTTP exceptions, `500` for AJAX requests
    (`CoreExceptionListener`).
  - `POST /deck/import/all` (zip archive, "uparchive"): one deck per file, named after the file
    (without folder nor extension); text files through the text import parser, `.o8d` through the
    OCTGN one; a file without any card gives an empty deck; a non-zip file or no file is a `422`.
    Fixed: the OCTGN parser (`BuilderController::parseOctgnImport`, also used by the single file
    import of a `.o8d`, `/deck/fileimport`) looked cards up by `Card.octgnid`, moved to
    `CardPrinting` by the card printings refactor ("Unrecognized field: octgnid", `500`, nothing
    imported). See "OCTGN features".
- Decklist edit / save / delete (`/decklist/edit|save|delete/{id}`, `DecklistEditTest`): no
  `access_control` rule for `/decklist/`, the controllers check the user; anonymous users are
  redirected to the login page on edit / save but get a `403` on delete (different exception
  classes). Only the author, or `ROLE_SUPER_ADMIN` (not `ROLE_ADMIN`), can edit. The predecessor
  is given as an id or a decklist URL; itself or an unknown id means none. Delete is refused
  (`403`) with votes, favorites or comments; decks copied from the decklist and its successors
  are attached to its predecessor. BUG-ish: a decklist used in a fellowship is silently removed
  from it (cascade remove on `Decklist.fellowships`: the fellowship stays published with the same
  `nb_decks`); quest logs are unlinked (`ON DELETE SET NULL`, they keep their copy of the cards).
  No CSRF protection on save and delete.
- Decklist favorites and votes (`POST /user/favorite`, `POST /user/like`, `DecklistSocialTest`):
  plain-text answer with the new count. Favorite is a toggle, +5 / -5 reputation for the author
  (not on one's own decklist). A vote cannot be taken back, is refused on one's own decklist, +1
  reputation. Fixed: `voteAction` did not check that the decklist exists (`getUser()` on null);
  it now answers `400`, `favoriteAction` `404` (both a `500` for AJAX, see
  `CoreExceptionListener`). No
  CSRF protection.
- Fixed: `POST /decklist/create` with an unknown or missing `deck_id` crashed (`getUser()` on
  null); it now answers `400 Bad Request`.

## Decklist comments

Covered by `tests/Controller/DecklistCommentTest.php`. The comment form is built in
JavaScript (`ui.decklist.js`) and posted with AJAX to `POST /user/comment` (`id`, `comment`); the
server answers with a redirect to the decklist. The tests restore the decklists' counters and
dates in `tearDown()` (the API's `Last-Modified` depends on them).

### Current behaviour pinned by the tests

- The comment is stored as HTML: Markdown rendered, bare URLs turned into links, then purified
  (HTMLPurifier: no `<script>`, no event handlers). `nb_comments`, `date_update` and
  `date_last_comment` of the decklist are updated.
- Notification emails ("[ringsdb] New comment", from `seastan@ringsdb.com` with the commenter's
  name): to the decklist author (`is_notif_author`), to previous commenters
  (`is_notif_commenter`) and to users mentioned as `` `@username` `` (`is_notif_mention`), never
  to the commenter. One email per recipient.
- An empty comment is silently ignored (redirect, nothing saved).
- Only the decklist author can hide/show a comment (`POST /user/hidecomment/{id}/{0|1}`); others
  get `200` with the JSON string "You don't have permission to edit this comment.". Hidden
  comments stay in the page, collapsed.

### To look at during the migration

- Fixed: commenting on an unknown decklist crashed on the final redirect (`getNameCanonical()`
  on null); it now answers `400 Bad Request`.
- BUG: for AJAX requests, `CoreExceptionListener` uses `$exception->getCode()` as the HTTP
  status instead of `getStatusCode()`: every HTTP exception (400, 403, 404...) becomes a `500`
  (with the right JSON message). Affects all AJAX calls, e.g. the comment form.
- No CSRF protection on `/user/comment` and `/user/hidecomment`.
- Emails are sent synchronously during the request, with `new \Swift_Message()` (SwiftMailer 6;
  `Swift_Message::newInstance()` was removed in SwiftMailer 6; SwiftMailer is replaced by Symfony
  Mailer in recent Symfony versions).

## Fellowships

Covered by `tests/Controller/FellowshipWorkflowTest.php`. The deck picker
(`app.deck_selection.js`) fills the hidden `deckN_id` / `deckN_is_decklist` fields of the form;
the tests fill them directly. Publishing a fellowship publishes its decks, which changes the
fixture decks: the tests restore them in `tearDown()`.

### Current behaviour pinned by the tests

- A fellowship holds 1 to 4 decks or decklists; empty slots are compacted (deck numbers 1..n).
  An empty fellowship is refused (`422`).
- Using another user's deck requires them to share their decks (`403` otherwise); the deck is
  then cloned for the current user.
- The publish form offers, for each deck, to publish it as a new decklist or to reuse a matching
  decklist (preselected when the deck is at version x.1 with a published child). Publishing
  replaces all decks by decklists, sets `is_public` and `date_publish`, and redirects to
  `/fellowship/view/{id}/{name_canonical}`.
- Hero conflicts (the same hero in two decks) prevent publishing (flash error).
- A published fellowship cannot be published again; its decks cannot be changed any more, its
  name and description can.
- A fellowship with votes, favorites or comments cannot be deleted; deleting a fellowship keeps
  its decks. `delete_list` takes `ids` as `1-2-3` and skips unknown or foreign ids.
- Another user's fellowship cannot be edited, saved, published or deleted (`403`).

### To look at during the migration

- BUG: "Save and Publish" (`auto_publish`) never publishes: `saveAction` tests
  `empty($fellowship->getDecks())`, and a Doctrine collection object is never `empty()`.
- No CSRF protection on `/fellowship/save`, `/fellowship/publish`, `/fellowship/delete`,
  `/fellowship/delete_list`.
- Fixed (found by phpstan): commenting on or voting for an unknown fellowship
  (`/user/fellowship_comment`, `/user/fellowship_like`) crashed on `null`; it now answers `400`,
  like the decklists.
- The fixture fellowship 1 is public but references decks (not decklists): a state the
  application itself does not produce. (It had no `date_publish` either, which Twig displayed as
  the current date: fixed in `LoadFellowshipData`.)

## Private API (`/api/private/*`)

Covered by `tests/Controller/ApiPrivateControllerTest.php`, same approach as the
public API (snapshots in `tests/Resources/snapshots/api/private/`). Requests are
sent with AJAX after logging in, like the site's JavaScript does.

### Current behaviour pinned by the tests

- `/decks`: the user's decklists then decks (descriptions emptied), newest first.
- `/decks_by_user/{username}`: same for the given user, but another user only gets the
  decklists, even if they share their decks (`$show_private_decks` ignores `is_share_decks`,
  commented out).
- `/deck/load/{id}`: a deck of the user, or of a user who shares their decks.
- `/custom-packs`: the user's custom packs.
- Errors (unknown user or deck, deck not shared) are `200` with
  `{"success": false, "error": ...}`.
- With data: `Last-Modified`, and `304` on `If-Modified-Since`; without data, no
  `Last-Modified`. `Cache-Control` is `max-age=0, must-revalidate, private` in both cases, set
  by the session listener since Symfony 3.4 (it was `private, must-revalidate` with data,
  `no-cache` without).
- Anonymous: `403` `{"success": false, "message": "Access Denied."}` for AJAX requests (through
  `CoreExceptionListener`), redirect to the login page otherwise.

## OAuth2 server (removed)

`FOSOAuthServerBundle` made RingsDB an OAuth2 server, so that third-party applications could act
on behalf of a user through `/api/oauth2/*` (inherited from ThronesDB). Token checking had been
disabled (the `api_oauth2` firewall was commented out), and the API had 4 routes: the session
user's decks, loading any shared deck (to anyone), and deck save / publish (both disabled).
Nothing called it any more: it was removed, with `GregwarCaptchaBundle`, which no form used.

Removed: the bundles (`AppKernel`, `composer.json`, `config.yml`), the `oauth_token` /
`oauth_authorize` firewalls and the `^/api/oauth2` access rule, the `/oauth/v2/*` and
`/api/oauth2/*` routes, `Oauth2Controller`, `CreateClientCommand`, `SecurityController` and its
template (the login form of the authorization endpoint), the templates overriding the bundle's,
the entities `Client`, `AccessToken`, `RefreshToken`, `AuthCode` and their mappings, the OAuth2
section of the API introduction page (`/api/`).

The `oauth2_*` tables are dropped by the Doctrine migration `Version20260929135555` (see
"Environment"), run by `deploy.sh`; `ringsdb_bootstrap.sql` still contains them, as the
production schema before the migrations.

## Quest logs

Covered by `tests/Controller/QuestlogWorkflowTest.php`. The deck picker fills the
hidden `deckN_id`, `deckN_is_decklist` and `questlogdeckN_content` fields; the tests fill them
directly.

### Current behaviour pinned by the tests

- A quest log stores its own copy of each deck's cards (`questlog_deck.content`, JSON as posted),
  next to the deck (and decklist, with its parent deck) it references, and the player's name
  (prefilled with the deck author's username). Slots are compacted.
- Scenario, date played, difficulty (`normal`, `easy`, `nightmare`; anything else becomes
  `normal`), victory (anything but `no` is a success), score (non numeric becomes 0), name
  (default "Untitled Questlog"), Markdown description.
- Checking "public" sets `is_public` and `date_publish` (on every save of a public quest log).
- Refused: no deck (`422`), a deck without content ("Cannot save a questlog with an empty
  deck", `200`), unknown scenario (`404`).
- Fixed: the quest log lists rebuild the decks from the logged content
  (`QuestLogController::setSnapshot()`, `Decks::setSlots()`): a card of the content missing from
  the database (e.g. not imported yet) made the page fail (undefined offset, then a `TypeError`
  on `setCard(null)`). It is now skipped (`testUnknownCardOfTheLoggedContentIsSkipped`).
- A quest log with votes, favorites or comments keeps its decks and visibility (the "public"
  checkbox is disabled); the other fields can still change. It cannot be deleted.
- Another user's deck requires them to share their decks and is cloned; a decklist needs no
  sharing.
- A private quest log is visible to its owner, and to others only if the owner shares their
  decks (`403` otherwise). Anonymous visitors are always redirected to the login page (see
  "Website browsing").
- Another user's quest log cannot be edited, saved or deleted (`403`).

### To look at during the migration

- BUG: the branch of `saveAction` meant for "the referenced deck was deleted" (`deckN_id` = 0
  with a content) reads `deckN_content`, but the form posts `questlogdeckN_content`: such a slot
  is silently dropped.
- The deck contents are decoded with `(array) json_decode(...)` (objects inside), like the deck
  builder does: `{"main": {}}` passes the "empty deck" guard.
- No CSRF protection on `/questlog/save`, `/questlog/delete`, `/questlog/delete_list`.
- Fixed (found by phpstan): commenting on or voting for an unknown quest log
  (`/user/questlog_comment`, `/user/questlog_like`) crashed on `null`; it now answers `400`.
- Fixed: the quest log list of a decklist page (`Decklist::getAllQuestlogs()`) was meant to include
  the quest logs of its parent deck, but they were assigned to a misspelled variable
  (`$parentlogs`) and never listed (found by phpstan).

## Card reviews

Covered by `tests/Controller/ReviewTest.php`. The forms of the card page are
posted with AJAX (`ui.card.js`, `ui.reviews.js`); on error, the JavaScript displays the
`message` of the JSON built by `CoreExceptionListener`.

### Current behaviour pinned by the tests

- Write (`/review/post`): one review per card and per user, and no more reviews than the user's
  reputation; not on a card whose primary printing's pack has no release date. Bare URLs become
  Markdown links; the text is rendered and purified. The 200-character minimum is only checked
  in the browser.
- Validation errors are generic `\Exception`s: `500` with the message in the JSON answer (the
  JavaScript relies on it). Anonymous users get a `403` JSON.
- Edit (`/review/edit`): own reviews only. An empty text returns a plain-text `200` "Your review
  is empty." (not JSON).
- Like (`/review/like`): +1 vote and +1 reputation for the author; once per user; liking your own
  review does nothing (still `success: true`).
- Comment (`/review/comment`): plain text, stored HTML-escaped; updates `date_last_comment`.
- Remove (`/review/remove/{id}`): `ROLE_SUPER_ADMIN` only (the fixture admin, `ROLE_ADMIN`,
  cannot); removes the votes too.

### To look at during the migration

- BUG: review comments are escaped twice (`htmlspecialchars()` when saved, Twig autoescape when
  displayed in `Search/display-card-reviews.html.twig` and `Reviews/reviews.html.twig`): `<b>`
  is shown as `&lt;b&gt;`.
- BUG (JavaScript): the comment form's error handler reads `jqXHR.responseBody.message`
  (`ui.card.js`) instead of `responseJSON`: errors are not displayed.
- For AJAX requests, the `403`/`400` HTTP exceptions of `/review/edit` become `500`s (see
  `CoreExceptionListener` above).
- `/review/remove/{id}` accepts any method, GET included, and has no CSRF protection (nor do
  the other review routes).

## Admin area (`/admin/*`)

Covered (read-only for now) by `tests/Controller/AdminPagesTest.php`: access
control (anonymous redirected to the login page, users `403`, also on write routes), and every
GET page as the fixture `admin` (ROLE_ADMIN): text snapshots in
`tests/Resources/snapshots/pages/admin/`, row counts for the card and card
printing lists (1300+ rows), JSON snapshots for the statistics (`?month=2015-08`, in
`snapshots/api/admin/`).

The Excel export / import is covered by `tests/Controller/AdminExcelTest.php`, by
round trip: download a pack (or all the cards), change the file with PhpSpreadsheet, upload it back
(field and association changes, card creation only with `create`, unknown association).

The write forms are covered by `tests/Controller/AdminWriteTest.php`, on records
created by the test only: the generated CRUD of the 8 reference entities (create → show,
edit → edit, delete → list, through the real forms, CSRF tokens included), scenario encounters,
card force delete, user search, comment hide/delete, decklist delete. Not covered yet: the card
image upload (unused, see below) and the scenario import command (`/admin/command/`, downloads
from hallofbeorn.com unless a custom JSON is given).

The Excel import is also tested with a download made in production
(`tests/Resources/fixtures/import/core-set.xlsx`), and the CSV import by
`tests/Controller/AdminCsvTest.php`, with the CSV of the ALeP pack "The Hobbit"
(`fixtures/import/alep-the-hobbit.csv`, 21 cards, already in the database): upload of an
unchanged pack, of a new pack, cards missing from the CSV, renaming with the old code, a CSV
without cards. The samples are stored without line ending conversion (`.gitattributes`): the
CSV import tells the rows (CRLF) from the line breaks of the texts (LF).

### CSV import (`POST /admin/csv/upload`, `CSVController`)

Fields `code`, `old_code`, `name` (the pack) and the file `upfile`. Header line + one card per
line, in the format produced by `BeornJSONtoRingsDBcsv.py` from a Hall of Beorn JSON export:
`pack, type, sphere, position, code, name, traits, text, flavor, isUnique, cost, threat,
willpower, attack, defense, health, victory, quest, quantity, deckLimit, illustrator, octgnid,
hasErrata`. Pinned by `AdminCsvTest`:

- The pack is found by code, then by old code (it is then renamed); otherwise it is created in
  the `ALeP` cycle, or the last one as there is no such cycle, released on 2030-02-01.
- The printings are found by `octgnid` in the pack; otherwise the card is found by code (a
  reprint: new printing of the existing card) or created.
- The cards of the pack missing from the CSV are not deleted: their name is prefixed with
  "[deleted]" and their code gets a unique suffix.
- BUG, not fixed: the card fields of the CSV (code, position, texts...) are written to the
  canonical card, even when the printing is a reprint. Uploading "The Hobbit" again gives Beorn
  (card 131005, from another pack) the code, position, text and flavor of its ALeP printing
  (503991). The card-level fields should only be written when the card was created by the
  import, or the printing is its first one.
- Only the rows ending with CRLF are rows: a CSV saved with LF line endings is read as a single
  row ("No cards found in the CSV file").

### Pending: scenario import test, waiting for a sample file

- **Scenario import** (`POST /admin/command/`, `command=scenario`,
  `ScrapBeornScenarioDataCommand`): downloads `http://hallofbeorn.com/LotR/ScenarioDetails/...`
  unless `customjson` is given. Needed: a saved Hall of Beorn scenario JSON, so the test never
  calls the network.

### To look at during the migration

- Fixed: `/admin/user/show/{id}` and the "Block" button crashed, FOSUserBundle 2 having dropped
  the "locked" feature: `User` kept the `$locked` property (the prod database still has the
  column) without accessors. `isLocked()` / `setLocked()` were added.
- Fixed: blocking a user had no effect, FOSUser 2's `isAccountNonLocked()` always returns
  `true` (production's FOSUser dev-master still honoured it). `User` overrides
  `isAccountNonLocked()`, `isAccountNonExpired()` and `isCredentialsNonExpired()` to read the
  columns again (covered by `testBlockedUserCannotLogIn`). Move to a `UserChecker` when
  replacing FOSUser.
- The user's "Date of last update" changes at each login (`last_login`, then Gedmo
  timestampable); it is masked in the admin user page snapshot.
- `StatController` concatenates the `month` query parameter into its SQL
  (`"SELECT '" . $month . "' AS month, ..."`): SQL injection, admin only. Use parameters.
- `/admin/stat` and `/admin/stat_packs` default to last month (`date()`): the tests pass a month.
  `/admin/stat_cards` reads `stat_cards_cache`, filled by the `app:stats:precompute-cards` cron,
  and answers `503` when it is empty.
- Excel import: the card texts are stored with CRLF line endings, the Excel file gives them back
  with LF, so uploading an unchanged download "changes" every card with a line break in its text
  or flavor (292 cards; their `date_update` changes too). An unknown type / sphere name stops the
  import with a generic exception (`500`, nothing saved). The upload `echo`es its report before
  returning its response.
- The Excel download is a `StreamedResponse`: Symfony's `StreamedResponseListener` sends it as
  soon as the controller returns it, so the test client gets an empty body (the tests capture it
  with an output buffer). The export file names come from `slugify()`, which drops the spaces
  (`lotrlcgcards.xlsx`).
- Done: PHPExcel (`liuggio/ExcelBundle`, abandoned) was replaced by PhpSpreadsheet. The files are
  same columns and values; whole numbers are read back as ints (floats with PHPExcel).
- Fixed: the "Delete" button of an admin pack page (`Pack/show.html.twig`) posted to
  `admin_cycle_delete`; the delete forms share their CSRF token, so it deleted the cycle with the
  same id as the pack (`testPackPageDeletesThePack`).
- The generated CRUD controllers of card, cycle, card printing, pack, encounter and scenario used
  the Symfony 2 `$form->bind($request)`, which ignores the request method. They now use
  `handleRequest()`; their edit and delete forms declare the `PUT` / `DELETE` methods that the
  templates send with the `_method` field (found by phpstan).
- Moderation actions are GET routes that write: `/admin/user/toggle_locked/{id}`,
  `/admin/decklist/delete/{id}`, `/admin/comment/toggle_hidden/{id}`,
  `/admin/comment/delete/{id}`.
- The generated CRUD controllers accept any method on `new`/`edit`/`show`; `/admin/command/`
  runs `ScrapBeornScenarioDataCommand` from a form.
- To be removed (probably unused in production, inherited from ThronesDB): the card image upload
  of the admin card form (unmapped `file` field in `CardType`, file move in
  `CardController::updateAction`). It writes `public/bundles/app/images/cards/<code>.png`, but the
  site reads card images from `public/bundles/cards/<code>.png` (and `<image_code>.png` for
  printings, `CardsData`; a symlink to `$CARD_IMAGES_DIR`, see "Environment"): uploaded images
  are never displayed. It also keeps the `.png` name
  whatever the actual format. Not tested.
- The generated CRUD controllers use the Symfony 2 form API (`createForm(new XxxType())`,
  `'entity'` / `'checkbox'` type names, `getName()`), which is gone in recent versions
  (`createForm(XxxType::class)`, FQCN types, `getBlockPrefix()`).
- Deleting reference data still in use (e.g. a cycle with packs, a card in decks) fails on the
  foreign keys with a `500` instead of an error message. `Card` has a "force delete" that
  removes its slots, printings and reviews, with SQL built by concatenation (the id comes from
  the route, `\d+` not enforced).

## Stable order of lists

User content lists were sorted by non-unique keys only (dates, popularity, votes), so ties came
back in an arbitrary order: flaky tests, and duplicated or missing items across pages in
production. An `id` tie-breaker was added (same direction as the main key, or `ASC` for
"oldest first" lists) to: the list methods of `DecklistManager`, `FellowshipManager` and
`QuestLogManager`, the home page lists (`DefaultController`), the review lists
(`DefaultController`, `ReviewController`, `CardsData::get_reviews`), the top decklists by card
(`ApiController`), the private API lists, the quest log lists (`QuestLogController`), the user
comment lists and decklist versions (`SocialController`), custom packs,
`Decks::getDecksWithSlotsForUser()`, and the card choice of the admin card printing form
(`CardPrintingType`, sorted by name: several cards share one; found when the `NOT NULL`
migration rebuilt the `card` table and MySQL returned the ties in another order).

- Fixed: in the "Hot Topics" lists (`DecklistManager`, `FellowshipManager`, `QuestLogManager`),
  `orderBy('d.nbComments')` replaced `orderBy('nbRecentComments')` instead of adding to it:
  recent comments were ignored. Now sorted by comments of the day, then by number of comments
  (`testHotTopicsFavourRecentComments`, decklists only: the fixtures have a single fellowship
  and a single quest log).

## Profile

Covered by `tests/Controller/UserProfileTest.php`: the site's profile form
(`/user/profile_edit` → `/user/profile_save`) and the FOSUserBundle forms (account
`/profile/edit`, password change `/profile/change-password`, password reset `/resetting/*`),
which have to be reimplemented when FOSUserBundle is removed.

### Current behaviour pinned by the tests

- Profile form: username (unique, checked), email, resume (tags stripped by
  `FILTER_SANITIZE_STRING`), sphere color, notification and sharing checkboxes (unticked =
  false), dark mode (also stored in a non-HttpOnly `dark_mode` cookie for 1 year, to apply the
  theme before the page is loaded). Renaming updates `username_canonical` (FOSUser listener):
  the user logs in with the new name. Redirects to the form with a flash message.
- FOSUser account and password forms require the current password ("The entered password is
  invalid."); password confirmation must match.
- Password reset: an email with a `/resetting/reset/{token}` link; a second request is ignored
  while the first one is recent (`retry_ttl`); no hint when the user does not exist; the token
  is single-use, an unknown or used token redirects to the login page (a `404` before
  FOSUserBundle 2.1); after the reset the user is logged in.

### To look at during the migration

- The site's profile form validates neither the email format nor its uniqueness: a duplicated
  email is only stopped by the database's unique index (`500`).
- No CSRF protection on `/user/profile_save`.

## Collection

Covered by `tests/Controller/CollectionTest.php`.

### Current behaviour pinned by the tests

- Owned packs (`/collection/packs/save`): `selected-packs`, a list of `id` / `id:count` tokens,
  stored as is in `user.owned_packs` (anything else than digits, `:`, `,` and `-` is refused
  with a plain-text "Invalid pack selection."). The collection page is rendered directly
  (forward, no redirect).
- Art preferences (`/collection/art/save`, AJAX from the card modal): `{card code: pack code}`
  JSON in `user.art_preferences`, `default` or empty removes the entry, `null` when empty.
- Custom packs: create / edit (cards posted as `cards_json`; unknown cards, duplicates and
  quantities outside 1..9 are skipped; the code is `custom_<id>_<6 hex>`), enable / publish
  toggles, delete, copy of a published pack by another user (JSON). Another user's pack is a
  `404`; the name is required.
- Flash messages are displayed by JavaScript (`app.ui.insert_alert_message(...)` in
  `layout.html.twig`), so they are JSON-encoded in the page source.

### To look at during the migration

- No CSRF protection on the collection and custom pack forms.

## Card statistics (`CardStatsCalculator`)

Per-card monthly usage statistics: for each card, the number of decks using it and the average
number of copies (capped at 3 per deck), in "full" decks (last pack released on or after
2019-08-02) and "limited" decks (older), plus sideboards and totals. Precomputed by the
`app:stats:precompute-cards` command (cron) into `stat_cards_cache` (a table without an entity,
excluded from the Doctrine schema by `schema_filter`), served as JSON by
`/admin/stat_cards` (admin only). Nothing in the repository consumes that JSON: probably an
external report made by an admin, to be confirmed (nginx logs, maintainers) before deciding
whether to keep it.

Covered by `tests/Stats/CardStatsCalculatorTest.php`: JSON snapshots of the 3 steps
on the fixture month (2015-08), and the counting rules on decks inserted by the test (copies
capped at 3, invalid decks and other months excluded, full vs limited, Messenger of the King
heroes counted as the card they copy and implying the contract 22134, sideboards, totals, the
2022 change of the month rule for private decks), plus the command.

- Fixed: the step 1 query did `GROUP BY c.code` while selecting non-aggregated columns
  (`cprim.octgnid`, from a derived table), rejected by MySQL 8's default `ONLY_FULL_GROUP_BY`.
  The selected columns were added to the `GROUP BY` (same result, one primary printing per
  card); production runs without `ONLY_FULL_GROUP_BY` (see "Environment").
- It relies on the `source_code()` MySQL stored function (`function-source-code.sql`), which
  `ringsdb_bootstrap.sql` does not contain: `make fixtures` / `make test-fixtures` now load it,
  as root (with binary logging, creating a function requires SUPER).
- The month is concatenated into the SQL; only the command validates its `YYYY-MM` format.
- Not covered: the merging of reprints by `source_code()` (none of the packs it handles are in
  the bootstrap data).
- MySQL-specific: temporary tables, `SET SESSION optimizer_switch`, stored function. The date
  thresholds, `pack_rules` and the excluded packs / spheres are hard-coded.

## Lists and search managers (`FellowshipManager`, `QuestLogManager`, `DecklistManager`)

The `find*()` methods of `FellowshipManager` and `QuestLogManager` are covered by
`tests/Model/FellowshipManagerTest.php` and `QuestLogManagerTest.php`, on data
inserted by the tests (not shared fixtures): every list (popularity, age, recent discussion,
favorites, author, hall of fame, hot topics), pagination, and the complex search (author,
name, number of decks, cards, packs, custom packs, number of Core Sets, sort orders).

- Fixed: the fellowship search by card or pack failed on MySQL 8 (`ONLY_FULL_GROUP_BY`): the
  "number of Core Sets" subqueries selected `jp.quantity` without grouping by it. Added to the
  `GROUP BY` (one Core Set printing per card).
- Fixed: the "sort by reputation" of the three searches (decklists, fellowships, quest logs)
  failed on MySQL 5.7+ (`DISTINCT` with an `ORDER BY` on a column that is not selected, error
  3065, whatever the `sql_mode`): the reputation is now selected as a hidden column.
- Fixed: `QuestLogManager` and 5 queries of `QuestLogController` used the entity alias
  `AppBundle:QuestLog` instead of `AppBundle:Questlog`: it only worked when the class was
  already loaded under its real name (PHP class names are case-insensitive), and would fail to
  autoload on a case-sensitive file system.

### Current behaviour pinned by the tests

- Only public items are listed, even for their author.
- Popularity is `(1 + votes) / (1 + days²)`, from `datePublish` in the lists but from
  `dateCreation` in the complex search.
- Fellowships are searched by card or pack through their decklists only: a fellowship made of
  (unpublished) decks is never found that way. Quest logs are searched through their decks.
- Several cards must be in the same deck / decklist. A pack filter matches when at least one of
  the item's decks only uses cards of the given packs (plus the user's selected custom packs,
  ignored for anonymous users).
- The "number of Core Sets" filters (fellowships only) are only applied with a card or pack
  filter; without a value they filter nothing.
- Removed: the `$ignoreEmptyDescriptions` parameter of the `ByAge` / `ByRecentDiscussion` methods,
  which was ignored (found by phpstan).

## Console commands

- `app:suggestions` (`SuggestionsCommand`, still used, `SuggestionsCommandTest`): computes which
  cards are used together in decks (all decks, private or not) and writes
  `public/suggestions.json`, loaded by the deck builder (`app.suggestions-mixed.js`, when the
  "show suggestions" option is on; `app.suggestions-statistics.js` and
  `app.suggestions-heuristics.js` are variants no template loads): `index` = the codes of the cards used in at least one deck, by card
  id; `matrix` = lower triangular, number of decks with both cards divided by
  `max(100, min(decks of each card))`, in percent. The output path comes from `$publicDir`
  (`%kernel.project_dir%/public`), and the command always
  overwrites the file: the test saves and restores it. The file is generated: no longer tracked by git
  (it was committed although listed in `.gitignore`), it has to be (re)generated on each server.
- `app:patron <email or username> [donation]` (`PatronCommand`, `PatronCommandTest`): the only way
  to record a donation (`user.donation`, cumulative; `> 0` makes a "Gracious Patron": badge next
  to the name, `/patrons` page, extra buttons in the play simulator through
  `/api/public/user/info`). Without an amount (or with 0) it shows the total. The amount is not
  checked (a negative one is subtracted); an unknown user is reported but exits with code 0.

- Card scraping commands (`app:beorn:html`, `app:beorn:json`, `app:download-images`): candidates
  for removal, see the roadmap. Removed: `app:cgdb:cards` (`ScrapCardDataCommand`, scraped
  cardgamedb.com), which used the console `DialogHelper` removed in Symfony 3.0.
- `app:remove-user` and `app:decklist:delete` now exit with code 1 when the user or decklist is
  not found (the latter crashed).

## OCTGN features

OCTGN is a desktop client for the game. Its deck files (`.o8d`, XML listing the cards by their
OCTGN id) can be exported and imported: these features are kept (decided on 2026-09-28, after
an earlier plan to drop them). The broken OCTGN commands were removed.

- Exports as `.o8d` (template `Export/octgn.xml.twig`, the `octgnid` of each card's primary
  printing): routes `deck_export_octgn`, `deck_export_octgn_list`, `decklist_export_octgn`,
  `fellowship_export_octgn`, `questlog_export_octgn`, with their buttons in the toolbars
  (`Builder/`, `Decklist/`, `Fellowship/`, `Quest/`, `QuestLog/toolbar.html.twig`), the My Decks
  page (`Builder/decks.html.twig`, `no-decks.html.twig`) and the scripts `ui.decklist.js`,
  `ui.decks.js`, `ui.fellowshipview.js`, `ui.questlogview.js`. Covered by the download snapshots
  (`WebsiteBrowsingTest`).
- Imports of `.o8d`: `BuilderController::parseOctgnImport()`, used by the single file import
  (`/deck/fileimport`, `Modale/file.html.twig`) and the archive import (`/deck/import/all`).
  Fixed: the parser looked the cards up by `Card.octgnid`, moved to `CardPrinting` by the card
  printings refactor, so both imports failed on a `.o8d`. The cards are now found by the octgnid
  of any of their printings (quantities of several printings of a card add up). Covered by an
  export → import round trip of the fixture decks (`DeckWorkflowTest`,
  `testOctgnExportCanBeImportedBack`) and by `DeckManagementTest::testImportAnArchiveWithAnOctgnFile`.
- The octgnid is not unique: 100 of them are shared by a hero and its Messenger of the King
  version ("(MotK) Guthlaf" has the octgnid of Guthlaf). OCTGN cannot tell them apart: the import
  chooses the original card (the lowest id, as before the refactor), so a MotK hero exported to
  OCTGN comes back as the original hero. 74 printings have no octgnid.
- Data: `CardPrinting.octgnid` (mapping, form `CardPrintingType`, admin templates `Card/`,
  `CardPrinting/`), filled by the CSV import (`CSVController`) and `BeornJSONtoRingsDBcsv.py`;
  returned by the public API for each card and printing, and by the card statistics (with an OCTGN
  id `mapping` of the reprints). Removed: `Sphere.octgnid` (inherited from ThronesDB, never set,
  used by nothing but its own admin form and pages; migration `Version20260930102154`).
- The "about" page mentions OCTGN (`Default/about.html.twig`).
- Removed: `UpdateOctgnCommand` (`app:octgn`), which still used a `Faction` entity (ThronesDB)
  and `Card::setOctgnid()`, so it could not run, and `ScrapOctgnCardDataCommand`
  (`app:cards:octgn`). The OCTGN parts of `ScrapBeornCardDataCommand` remain (see the roadmap,
  card scraping commands).

## Card search

Covered by `tests/Controller/CardSearchTest.php` (public API
`/api/public/cards/search/{q}` and site search `/find`).

- Fixed: a search term in capitals (2 letters or more) is also searched as an acronym
  (`CardsData`, name search): as the initials of the words, dashes counting as spaces
  (`REPLACE(c.name, '-', ' ') LIKE 'S% O% G%'`, `App\DQL\ReplaceFunction`). The `replace`
  DQL function had never been registered (since the conversion from ThronesDB), so every such
  search failed with a `500` ("Expected known function, got 'REPLACE'"). Registered in
  `config.yml`: "SOG" finds "Steward of Gondor" (and "Soldier of Gondor"), "LOS" "Longbeard Orc
  Slayer".
- The custom DQL functions (`replace`, `power`) are MySQL-specific; Doctrine ORM 2.x
  has no built-in equivalent (DoctrineExtensions provides them).

## Removed dead code

Code no route, template, script or other code could reach (found with the coverage report),
removed before the migration so that it does not have to be ported:

- `QuestLogManager::findQuestLogsByRecentDiscussion()`: its query could not work (`Questlog` has no
  `dateLastComment` field, found by the tests then by phpstan-doctrine's DQL check), and nothing
  called it.

- `Texts::truncate()`: never called.
- `SocialController::findSimilarDecklists()`: never called (already marked "(unused)").
- `Default/recent_reviews.html.twig`: included by no template nor controller (it also read a
  `review.donation` that reviews do not have).
- `SocialController::usercommentsAction()` and `commentsAction()`, with their templates
  `Default/usercomments.html.twig` and `Default/allcomments.html.twig`: the comment lists of a
  user and of the whole site. Their routes were reused for the user admin panel on 2016-04-01
  (commit `497ccf27`); the admin pages `/admin/user/comments/{user_id}`
  (`UserAdminController::commentsAction`) replace them.
- The FOSUserBundle group templates (`app/Resources/FOSUserBundle/views/Group/`): the groups are
  not used.
- `app:twig` (`TwigCacheCommand`): called `Twig_Environment::getCacheFilename()`, removed in
  Twig 2, so it crashed (found by phpstan).
- `Decklist::$is_simple_export` and its accessors: never used.
- `App\DQL\BinaryFunction` and the `BINARY(c.name) LIKE '%SOG%'` condition of the acronym
  search (`CardsData`, name search): a case-sensitive search of the acronym in the name, which
  matches no RingsDB card (no card name has 2 capitals in a row). The initials condition
  (`REPLACE`) finds all the results.

## After the migration

- The single text exports (`BuilderController::textexportAction`,
  `SocialController` decklist text export) convert the line endings to CRLF
  (`str_replace("\n", "\r\n", ...)`), the zipped exports do not. Keep CRLF until the migration
  is done, then switch to LF (and regenerate `downloads/deck_1.txt` and
  `downloads/decklist_1.txt`).
- `.gitattributes` stores the test snapshots without line ending conversion (`-text`), so that
  the byte-for-byte comparisons do not depend on each developer's `core.autocrlf`.

## Tests and time

The home page's "Daily Challenge" is picked with `srand(<day number>)` (`DefaultController`): it
is masked in the home page snapshots. Dates written during the tests come from `new \DateTime()` (controllers, `DecklistFactory`) and
from Gedmo timestampable, so the tests restore them in `tearDown()` (decklists, fixture decks)
and only check them loosely. Plan: once on a recent Symfony, use `Symfony\Component\Clock`
(`ClockInterface`, `MockClock` in tests) everywhere, including for the timestampable fields, to
freeze the time in all tests.
