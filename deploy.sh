#!/bin/bash
# One-shot redeploy for a RingsDB checkout.
#
# Operates on its OWN directory (via dirname "$0"), so it is safe to run from
# any of the three forks: ./deploy.sh from /var/www/ringsdb_test redeploys test,
# from /var/www/ringsdb redeploys production, etc.
#
# Steps: switch to maintenance mode, snapshot the database, fast-forward the
# branch, install the dependencies (the Composer scripts clear the cache and
# build the assets), link the card images, apply the Doctrine migrations,
# refresh the cache/log ACLs, leave maintenance mode. If a step fails, the site
# stays in maintenance mode.
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
CONSOLE="php app/console"

# read by app/console, including in the Composer scripts (the dev bundles are
# not installed)
export SYMFONY_ENV=prod

echo "==> Redeploying $ROOT"

# --- 0. Check the card images -------------------------------------------------
# assets:install (a Composer script) deletes every directory of web/bundles/
# that is not a bundle's: a real web/bundles/cards directory would be lost. A
# symlink is only unlinked, its target is kept.
if [ -d web/bundles/cards ] && [ ! -L web/bundles/cards ]; then
    echo "!! web/bundles/cards is a directory: the deploy would delete it." >&2
    echo "   Move it out of the checkout first, then set CARD_IMAGES_DIR, e.g.:" >&2
    echo "     mv web/bundles/cards /var/www/card-images" >&2
    echo "     export CARD_IMAGES_DIR=/var/www/card-images" >&2
    exit 1
fi
if [ -z "${CARD_IMAGES_DIR:-}" ] || [ ! -d "$CARD_IMAGES_DIR" ]; then
    echo "!! CARD_IMAGES_DIR must be set to the directory of the card images." >&2
    exit 1
fi
# absolute, so that the symlink does not depend on where it is
CARD_IMAGES_DIR="$(cd "$CARD_IMAGES_DIR" && pwd)"

# --- 1. Maintenance mode -----------------------------------------------------
# web/app.php answers 503 with web/maintenance.html while the flag exists, so
# nothing writes to the database or the cache during the update.
MAINTENANCE_FLAG="$ROOT/maintenance.flag"
SNAPSHOT=""
if [ "${MAINTENANCE:-1}" != "0" ]; then
    echo "==> Maintenance mode on..."
    touch "$MAINTENANCE_FLAG"
    trap 'if [ $? -ne 0 ]; then
              echo "!! The deploy failed: the site stays in maintenance mode." >&2
              echo "   Database snapshot: ${SNAPSHOT:-none}" >&2
              echo "   Once fixed, leave maintenance mode: rm $MAINTENANCE_FLAG" >&2
          fi' EXIT
    # the requests in progress finish
    sleep 10
fi

# --- 2. Snapshot the database -------------------------------------------------
# The connection comes from app/config/parameters.yml; the password goes
# through a defaults file, not the command line.
SNAPSHOT_DIR="${SNAPSHOT_DIR:-$HOME/db-snapshots}"
# the snapshots hold the users' data (emails, password hashes): owner only
mkdir -p "$SNAPSHOT_DIR"
chmod 700 "$SNAPSHOT_DIR"

db_parameter() {
    php -r 'require "vendor/autoload.php";
        $p = Symfony\Component\Yaml\Yaml::parse(file_get_contents("app/config/parameters.yml"))["parameters"];
        echo $p[$argv[1]] ?? "";' "$1"
}
DB_HOST="$(db_parameter database_host)"
DB_PORT="$(db_parameter database_port)"
DB_NAME="$(db_parameter database_name)"
DB_USER="$(db_parameter database_user)"
DB_PASSWORD="$(db_parameter database_password)"

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
OLD_HEAD="$(git rev-parse HEAD)"
BRANCH="$(git rev-parse --abbrev-ref HEAD)"

if [ "${SKIP_PULL:-0}" != "1" ]; then
    echo "==> Fetching origin and fast-forwarding $BRANCH..."
    git fetch origin
    if git rev-parse --abbrev-ref --symbolic-full-name '@{u}' >/dev/null 2>&1; then
        # --ff-only refuses to merge if the branch has diverged: fail loudly
        # rather than create a merge commit or rewrite history.
        git merge --ff-only '@{u}'
    else
        echo "    (no upstream configured for $BRANCH; skipping merge)"
    fi
fi
NEW_HEAD="$(git rev-parse HEAD)"

if [ "$OLD_HEAD" = "$NEW_HEAD" ]; then
    echo "    Already up to date ($NEW_HEAD)."
else
    echo "    $OLD_HEAD -> $NEW_HEAD"
fi

# --- 4. Install the dependencies ----------------------------------------------
# The Composer scripts then build parameters.yml, clear the cache, install and
# dump the assets.
# The prod kernel uses its cached container without checking whether it is up
# to date: remove it, or the cache:clear of the Composer scripts would boot the
# container of the previous code. Renamed first: the move is instant, even if
# requests still write to the cache.
echo "==> Installing the dependencies..."
if [ -d app/cache/prod ]; then
    mv app/cache/prod "app/cache/prod.old.$(date +%s)"
fi
rm -rf app/cache/prod.old.*
composer install --no-dev --optimize-autoloader --no-interaction

echo "==> Linking the card images ($CARD_IMAGES_DIR)..."
ln -sfn "$CARD_IMAGES_DIR" web/bundles/cards

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
    setfacl -R  -m u:rings:rwX -m u:www-data:rwX app/cache app/logs 2>/dev/null || true
    setfacl -dR -m u:rings:rwX -m u:www-data:rwX app/cache app/logs 2>/dev/null || true
fi

# --- 7. Leave maintenance mode ------------------------------------------------
if [ -f "$MAINTENANCE_FLAG" ]; then
    echo "==> Maintenance mode off."
    rm -f "$MAINTENANCE_FLAG"
fi

echo "==> Done."
