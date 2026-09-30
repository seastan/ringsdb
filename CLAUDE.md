# RingsDB — Dev Notes for Claude

## Running the local instance

The dev stack is PR #214's `docker-compose.yaml` (MySQL 8.0, same as prod; PHP 7.4 with
`app/console server:run`, dev environment), plus a local, untracked
`docker-compose.override.yaml` that builds the `symfony` image from
`docker/local/symfony.Dockerfile` with `www-data` remapped to the host uid/gid. Repo files are
mode 640, so without the remap the container cannot read them.

```bash
docker compose up -d        # mysql, mysql_test, symfony, adminer
```

- App: http://localhost:8080/ (dev env, debug toolbar); adminer: http://localhost:8081/
- DB: service `mysql`, db `ringsdb`, `symfony` / `passwd` (root: `passwd`); no host port, use
  `docker compose exec mysql mysql -uroot -ppasswd ringsdb`
- Test DB: service `mysql_test`, db `ringsdb_test` (`config_test.yml`)
- `app/config/parameters.yml` (local, untracked) points at `mysql:3306`, `ringsdb`,
  `symfony` / `passwd`, with `channel: http`.
- Card images live outside the checkout in `../card_images` (`CARD_IMAGES_DIR`); the
  entrypoint links `web/bundles/cards` to them. Never make `web/bundles/cards` a real
  directory: the Composer scripts wipe it.

Login with `tester` / `test1234`. It is a local account (`fos:user:create`), not in the dumps.

The old MariaDB/Apache stack for `master` is `docker-compose.mariadb.yml` (local, untracked):
`docker compose -f docker-compose.mariadb.yml up -d`, with its `parameters.yml` saved as
`app/config/parameters.yml.mariadb.bak`.

### First-time setup (new machine / fresh volume)

Run the console as the container user: `X="docker compose exec -T -u www-data symfony"`.

1. **vendor/**: `$X composer install` (Composer 2.2 is in the image). On `master` use the rsync
   path instead (see Development workflow).

2. **DB**: two options.

   **(a) Public bootstrap (no prod data / no PII):** `make fixtures` (**drops the dev DB**; bootstrap schema and card
   data, Doctrine migrations, then test fixtures).

   **(b) Full prod dump (maintainers with data access only).** `ringsdb_daily.sql` (~695 MB,
   from 2026-06) predates the hand-written scripts that the PR deleted from `migrations/` (all
   applied in prod), so replay them from `master`, then add the `user.dark_mode` column prod
   gained without a script, then run the Doctrine migrations:
   ```bash
   M="docker compose exec -T mysql mysql -uroot -ppasswd ringsdb"
   $M < ringsdb_daily.sql
   for f in card-printings/01_schema card-printings/02_migrate card-printings/03_user_art_preferences \
            card-printings/04_cleanup custom-packs/01_schema custom-packs/02_published \
            stats-precompute/01_stat_cards_cache; do
     git show master:migrations/$f.sql | $M
   done
   $M -e "ALTER TABLE user ADD dark_mode TINYINT(1) NOT NULL DEFAULT '0'"
   $X php app/console doctrine:migrations:migrate -n
   $X php app/console doctrine:schema:validate
   $X php app/console fos:user:create tester tester@localhost.invalid test1234
   ```

3. **Card images** (~832 MB, needed for card/deck pages):
   `rsync -az rings@ringsdb.com:/var/www/ringsdb/web/bundles/cards/ ../card_images/`

### Tests and checks

The Makefile targets use `exec -it`; without a terminal, run the same commands with `-T`.
`make test-fixtures` rebuilds the test DB, then `bin/simple-phpunit`, `bin/phpstan` (after
`cache:warmup --env=test`) and `lint:twig`. The snapshots assume a bare environment: run
PHPUnit with **no card images** (`CARD_IMAGES_DIR=<empty dir> docker compose up -d symfony`,
otherwise `imagesrc` is filled in) and **`game_name: ~`** in `parameters.yml` (it appears in
page titles), then restore both.

### Key gotchas

- **Login form**: the page has two forms — submit via Enter in the password field (the first
  submit button on the page belongs to the card-search form, not login).
- **Old card codes in JSON**: quest log snapshots (and deck history) store card codes from
  before the card-printings merge, e.g. `31031`, now a printing (`card_printing.image_code`)
  of card `17143`. Look up codes with that fallback (`Decks::findCardByCode`).

## Development workflow

- `master` (pre-upgrade): `vendor/` is rsynced from the server, do NOT run `composer install`
  there (its lock is Composer-1 era; Composer 2 resolves it to versions that break
  FOSUserBundle): `rsync -az rings@ringsdb.com:/var/www/ringsdb/vendor/ vendor/` then
  `composer dump-autoload` (Composer 1). `symfony-upgrade`: `composer install` from the lock.
  Switching branches means reinstalling `vendor/`.
- Validate migration SQL by parsing `ringsdb_daily.sql` with Python (the SQL dump is ~695 MB at
  repo root). Current card/set reference data lives in the committed `ringsdb_bootstrap.sql`
  (see first-time setup); the old `card-data.sql` / `packs-data.sql` / `scenario-data.sql`
  snapshots were stale and have been removed.
- Deploy by pushing to the feature branch; pull on `ringsdb.com` test server over SSH
  (`rings@ringsdb.com`).
