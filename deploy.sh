#!/bin/bash
# One-shot redeploy for a RingsDB checkout.
#
# Operates on its OWN directory (via dirname "$0"), so it is safe to run from
# any of the three forks: ./deploy.sh from /var/www/ringsdb_test redeploys test,
# from /var/www/ringsdb redeploys production, etc.
#
# Steps: check the card images, Composer and the PHP extensions needed by the
# version to deploy (before any change), switch to maintenance mode, snapshot
# the database, fast-forward the branch, back up vendor/, install the
# dependencies (the Composer scripts clear the cache and build the assets), link
# the card images, apply the Doctrine migrations, refresh the cache/log ACLs,
# leave maintenance mode. If a step fails, the site stays in maintenance mode.
#
# Env:
#   CARD_IMAGES_DIR  (required) the card images, outside the checkout: served
#                    as /bundles/cards through a symlink
#   SKIP_PULL=1      skip git fetch/fast-forward
#   MAINTENANCE=0    keep the site up during the deploy
#   SNAPSHOT_DIR     where the database snapshots go (default: ~/db-snapshots)
set -euo pipefail

cd "$(dirname "$0")"
ROOT="$(pwd)"
CONSOLE="php bin/console"

# read by config/bootstrap.php, including in the Composer scripts (the dev
# bundles are not installed); a real environment variable wins over the .env
# files
export APP_ENV=prod

echo "==> Redeploying $ROOT"

# --- 0. Checks, before any change ---------------------------------------------
# The card images.
# assets:install (a Composer script) deletes every directory of public/bundles/
# that is not a bundle's: a real public/bundles/cards directory would be lost. A
# symlink is only unlinked, its target is kept.
if [ -d public/bundles/cards ] && [ ! -L public/bundles/cards ]; then
    echo "!! public/bundles/cards is a directory: the deploy would delete it." >&2
    echo "   Move it out of the checkout first, then set CARD_IMAGES_DIR, e.g.:" >&2
    echo "     mv public/bundles/cards /var/www/card-images" >&2
    echo "     export CARD_IMAGES_DIR=/var/www/card-images" >&2
    exit 1
fi
# web/ was the document root before the Symfony 4 layout (public/ now).
if [ -d web/bundles/cards ] && [ ! -L web/bundles/cards ]; then
    echo "!! web/bundles/cards is a directory (the former document root)." >&2
    echo "   Move it out of the checkout first, then set CARD_IMAGES_DIR, e.g.:" >&2
    echo "     mv web/bundles/cards /var/www/card-images" >&2
    echo "     export CARD_IMAGES_DIR=/var/www/card-images" >&2
    exit 1
fi

# The configuration: the values of this server for the environment variables of
# .env (committed defaults), in .env.local (formerly app/config/parameters.yml).
if [ ! -f .env.local ] && [ ! -f .env.prod.local ]; then
    echo "!! No .env.local: create it with the values of this server (the variables" >&2
    echo "   of .env, APP_ENV=prod included; formerly app/config/parameters.yml)." >&2
    exit 1
fi
if [ -z "${CARD_IMAGES_DIR:-}" ] || [ ! -d "$CARD_IMAGES_DIR" ]; then
    echo "!! CARD_IMAGES_DIR must be set to the directory of the card images." >&2
    exit 1
fi
# absolute, so that the symlink does not depend on where it is
CARD_IMAGES_DIR="$(cd "$CARD_IMAGES_DIR" && pwd)"

# Composer: the lock is a Composer 2 lock, and check-platform-reqs --lock needs
# Composer 2.2.
COMPOSER_MIN_VERSION=2.2.0
COMPOSER_VERSION="$(composer --version --no-ansi 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1 || true)"
if [ -z "$COMPOSER_VERSION" ] \
    || ! php -r 'exit(version_compare($argv[1], $argv[2], ">=") ? 0 : 1);' "$COMPOSER_VERSION" "$COMPOSER_MIN_VERSION"; then
    echo "!! Composer >= $COMPOSER_MIN_VERSION is needed (found: ${COMPOSER_VERSION:-none})." >&2
    exit 1
fi

# The version to deploy: the upstream branch, fetched now so that its PHP
# requirements are checked before the site is touched.
OLD_HEAD="$(git rev-parse HEAD)"
BRANCH="$(git rev-parse --abbrev-ref HEAD)"
TARGET="$OLD_HEAD"
if [ "${SKIP_PULL:-0}" != "1" ]; then
    echo "==> Fetching origin..."
    git fetch origin
    if git rev-parse --abbrev-ref --symbolic-full-name '@{u}' >/dev/null 2>&1; then
        TARGET="$(git rev-parse '@{u}')"
        # the fast-forward of step 3 must be possible: fail now, not halfway
        if ! git merge-base --is-ancestor HEAD "$TARGET"; then
            echo "!! $BRANCH has diverged from its upstream: no fast-forward possible." >&2
            exit 1
        fi
    else
        echo "    (no upstream configured for $BRANCH; deploying the current commit)"
    fi
fi

# The fast-forward of step 3 must not trip over local changes (a tracked file
# changed on the server that the merge changes too, an untracked file the merge
# adds): dry run of the same checkout, nothing is changed.
# (update-index only refreshes the file stats: a file merely touched, e.g. by
# rsync, is not a change)
git update-index -q --refresh || true
if [ "$TARGET" != "$OLD_HEAD" ] && ! git read-tree -mun HEAD "$TARGET"; then
    echo "!! Local changes would be overwritten by the update (see above): nothing was changed." >&2
    exit 1
fi

# The PHP version and extensions required by the lock file of that version.
echo "==> Checking the PHP requirements of $TARGET..."
PLATFORM_DIR="$(mktemp -d)"
git show "$TARGET:composer.json" > "$PLATFORM_DIR/composer.json"
git show "$TARGET:composer.lock" > "$PLATFORM_DIR/composer.lock"
if ! COMPOSER="$PLATFORM_DIR/composer.json" composer check-platform-reqs --lock --no-dev --no-ansi; then
    rm -rf "$PLATFORM_DIR"
    echo "!! The PHP requirements of $TARGET are not met (see above): nothing was changed." >&2
    exit 1
fi
rm -rf "$PLATFORM_DIR"

# --- 1. Maintenance mode -----------------------------------------------------
# public/index.php answers 503 with public/maintenance.html while the flag exists, so
# nothing writes to the database or the cache during the update.
MAINTENANCE_FLAG="$ROOT/maintenance.flag"
SNAPSHOT=""
if [ "${MAINTENANCE:-1}" != "0" ]; then
    echo "==> Maintenance mode on..."
    touch "$MAINTENANCE_FLAG"
    trap 'if [ $? -ne 0 ]; then
              echo "!! The deploy failed: the site stays in maintenance mode." >&2
              echo "   To roll back: git reset --hard $OLD_HEAD, restore vendor.bak/" >&2
              echo "   (if any) as vendor/, rm -rf var/cache/prod, and if the migrations ran," >&2
              echo "   restore the database snapshot: ${SNAPSHOT:-none}" >&2
              echo "   Then leave maintenance mode: rm $MAINTENANCE_FLAG" >&2
          fi' EXIT
    # the requests in progress finish
    sleep 10
fi

# --- 2. Snapshot the database -------------------------------------------------
# The connection comes from the DATABASE_* variables (.env, .env.local,
# loaded by config/bootstrap.php); the password goes
# through a defaults file, not the command line.
SNAPSHOT_DIR="${SNAPSHOT_DIR:-$HOME/db-snapshots}"
# the snapshots hold the users' data (emails, password hashes): owner only
mkdir -p "$SNAPSHOT_DIR"
chmod 700 "$SNAPSHOT_DIR"

db_parameter() {
    php -r 'require "config/bootstrap.php";
        echo $_SERVER[$argv[1]] ?? "";' "$1"
}
DB_HOST="$(db_parameter DATABASE_HOST)"
DB_PORT="$(db_parameter DATABASE_PORT)"
DB_NAME="$(db_parameter DATABASE_NAME)"
DB_USER="$(db_parameter DATABASE_USER)"
DB_PASSWORD="$(db_parameter DATABASE_PASSWORD)"

SNAPSHOT="$SNAPSHOT_DIR/${DB_NAME}_$(date +%Y%m%d-%H%M%S).sql.gz"
echo "==> Snapshotting $DB_NAME to $SNAPSHOT..."
# Without the tablespaces (they need the PROCESS privilege) nor the routines:
# the source_code() stored function was created by root, the application user
# cannot dump it. To restore, load the snapshot then function-source-code.sql
# (as root).
mysqldump --defaults-extra-file=<(printf '[client]\nuser=%s\npassword=%s\nhost=%s\nport=%s\n' \
        "$DB_USER" "$DB_PASSWORD" "${DB_HOST:-127.0.0.1}" "${DB_PORT:-3306}") \
    --single-transaction --no-tablespaces "$DB_NAME" | gzip > "$SNAPSHOT"

# --- 3. Update code (fast-forward only; never clobber local commits) ----------
# The commit checked in step 0, not a newer push.
if [ "$TARGET" != "$OLD_HEAD" ]; then
    echo "==> Fast-forwarding $BRANCH..."
    # --ff-only refuses to merge if the branch has diverged: fail loudly rather
    # than create a merge commit or rewrite history.
    git merge --ff-only "$TARGET"
fi
NEW_HEAD="$(git rev-parse HEAD)"

if [ "$OLD_HEAD" = "$NEW_HEAD" ]; then
    echo "    Already up to date ($NEW_HEAD)."
else
    echo "    $OLD_HEAD -> $NEW_HEAD"
fi

# --- 4. Install the dependencies ----------------------------------------------
# The Composer scripts then clear the cache, install and build the assets.
# The prod kernel uses its cached container without checking whether it is up
# to date: remove it, or the cache:clear of the Composer scripts would boot the
# container of the previous code. Renamed first: the move is instant, even if
# requests still write to the cache.
# vendor/ is backed up first, for a rollback (the previous backup is replaced).
echo "==> Backing up vendor/ to vendor.bak/..."
rm -rf vendor.bak
cp -a vendor vendor.bak

echo "==> Installing the dependencies..."
if [ -d var/cache/prod ]; then
    mv var/cache/prod "var/cache/prod.old.$(date +%s)"
fi
rm -rf var/cache/prod.old.*
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Linking the card images ($CARD_IMAGES_DIR)..."
ln -sfn "$CARD_IMAGES_DIR" public/bundles/cards

# --- 5. Apply the database migrations -----------------------------------------
echo "==> Applying the migrations (snapshot: $SNAPSHOT)..."
$CONSOLE doctrine:migrations:migrate --no-interaction --allow-no-migration

# --- 6. Refresh cache/log ACLs (best-effort, no sudo) -------------------------
# New files already inherit the right ACLs from the parent dirs' *default* ACL
# entries, so this is only a safety net for files this run just created. The
# current user owns those, so setfacl works without sudo (rings is not in
# sudoers for setfacl anyway). Stay quiet and never fail the deploy over perms.
# Use setfacl, NOT chown — chown would strip the ACLs the web server needs.
echo "==> Refreshing cache/log ACLs (best-effort)..."
if command -v setfacl >/dev/null 2>&1; then
    setfacl -R  -m u:rings:rwX -m u:www-data:rwX var/cache var/log 2>/dev/null || true
    setfacl -dR -m u:rings:rwX -m u:www-data:rwX var/cache var/log 2>/dev/null || true
fi

# --- 7. Leave maintenance mode ------------------------------------------------
if [ -f "$MAINTENANCE_FLAG" ]; then
    echo "==> Maintenance mode off."
    rm -f "$MAINTENANCE_FLAG"
fi

echo "==> Done."
