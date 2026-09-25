# RingsDB — Dev Notes for Claude

## Running the local instance

```bash
docker compose up -d
```

- App (prod front controller): http://localhost:8080/
- App (dev front controller, debug toolbar): http://localhost:8080/app_local.php
- DB port: localhost:3307 (root / `ringsdb`, db `symfony`)

Login with `tester` / `test1234` (or a prod account).

### First-time setup (new machine / fresh volume)

1. **DB**: two options.

   **(a) Public bootstrap (recommended, no prod data / no PII).** `ringsdb_bootstrap.sql`
   (committed at repo root) is the full schema for every table plus reference data for the
   card/set/cycle/printing tables only (`card`, `card_printing`, `cycle`, `pack`, `sphere`,
   `type`, `encounter`, `scenario`, `scenario_encounter`). Every other table (users, decks,
   decklists, comments, votes, …) is created empty. It already reflects the current prod
   schema, so **do not** re-run the `migrations/` scripts on top of it — they are baked in.
   Register a fresh account once the app is up (the seeded `tester` login only exists in the
   prod dump).
   ```bash
   docker exec -i ringsdb-db-1 mysql -uroot -pringsdb symfony < ringsdb_bootstrap.sql
   ```

   **(b) Full prod dump (maintainers with data access only).** Import the prod dump then
   apply the migrations it predates:
   ```bash
   docker exec -i ringsdb-db-1 mysql -uroot -pringsdb symfony < ringsdb_daily.sql
   docker exec -i ringsdb-db-1 mysql -uroot -pringsdb symfony < migrations/card-printings/01_schema.sql
   docker exec -i ringsdb-db-1 mysql -uroot -pringsdb symfony < migrations/card-printings/02_migrate.sql
   docker exec -i ringsdb-db-1 mysql -uroot -pringsdb symfony < migrations/card-printings/03_user_art_preferences.sql
   ```

2. **vendor/**: rsync from server (do NOT run `composer install` — lock is Composer-1 era,
   Composer-2 drifts Symfony 2.7→2.8 and breaks FOSUserBundle). `vendor/` is gitignored
   (untracked as of `chore/untrack-vendor`); front controllers (`web/app.php`,
   `web/app_dev.php`, `app/console`) load `vendor/autoload.php` directly rather than a
   generated `app/bootstrap.php.cache`, so no separate bootstrap-build step is needed:
   ```bash
   rsync -az rings@ringsdb.com:/var/www/ringsdb/vendor/ vendor/
   composer dump-autoload   # Composer 1
   ```

3. **Card images** (~832 MB, optional but needed for card/deck pages):
   ```bash
   chown -R $(id -u) web/bundles
   rsync -az rings@ringsdb.com:/var/www/ringsdb/web/bundles/cards/ web/bundles/cards/
   ```

### Key gotchas

- **Apache uid**: `docker-compose.yml` passes `APACHE_RUN_USER: "#<uid>"` so Apache workers
  run as your host uid. Repo files are mode 640 — if this is wrong you get misleading
  "class not found / service not found" errors, not permission errors.
- **Cache permissions**: after any `cache:clear` run inside the container (which runs as root),
  fix ownership or login silently fails:
  ```bash
  docker exec ringsdb-web-1 chown -R 1433601250 app/cache app/logs
  ```
- **Login form**: the page has two forms — submit via Enter in the password field (the first
  submit button on the page belongs to the card-search form, not login).
- **Local-only config files** (not committed): `parameters.yml`, `app/config/config_prod.yml`,
  `app/config/config_dev.yml`. These disable https-force (`channel: http`) and enable live
  assets (`assetic.use_controller: true`).

## Development workflow

- No Composer reinstalls. See vendor/ note above.
- Validate migration SQL by parsing `ringsdb_daily.sql` with Python (the SQL dump is ~695 MB at
  repo root). Current card/set reference data lives in the committed `ringsdb_bootstrap.sql`
  (see first-time setup); the old `card-data.sql` / `packs-data.sql` / `scenario-data.sql`
  snapshots were stale and have been removed.
- Deploy by pushing to the feature branch; pull on `ringsdb.com` test server over SSH
  (`rings@ringsdb.com`).
