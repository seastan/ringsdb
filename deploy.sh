#!/bin/bash
# One-shot redeploy for a RingsDB checkout.
#
# Operates on its OWN directory (via dirname "$0"), so it is safe to run from
# any of the three forks: ./deploy.sh from /var/www/ringsdb_test redeploys test,
# from /var/www/ringsdb redeploys production, etc.
#
# Steps: fast-forward the branch, then clear the prod cache and recompile assets.
# It deliberately does NOT auto-apply DB migrations or run composer — it stops
# and tells you when the pull brings those in, so you can snapshot the DB first.
#
# Env toggles:
#   SKIP_PULL=1             skip git fetch/fast-forward (cache+assets only)
#   SKIP_MIGRATION_CHECK=1  proceed even though new migration files were pulled
#                           (set this after you've applied them by hand)
set -euo pipefail

cd "$(dirname "$0")"
ROOT="$(pwd)"
CONSOLE="php app/console"

echo "==> Redeploying $ROOT"

# --- 1. Update code (fast-forward only; never clobber local commits) ----------
OLD_HEAD="$(git rev-parse HEAD)"
BRANCH="$(git rev-parse --abbrev-ref HEAD)"

if [ "${SKIP_PULL:-0}" != "1" ]; then
    echo "==> Fetching origin and fast-forwarding $BRANCH..."
    git fetch origin
    if git rev-parse --abbrev-ref --symbolic-full-name '@{u}' >/dev/null 2>&1; then
        # vendor/ is populated via rsync, not composer install, and is meant to
        # stay on disk untouched by deploys. But if it's still *tracked* here
        # while the upstream commit we're about to fast-forward to has stopped
        # tracking it, the fast-forward's checkout will delete it from disk
        # (git removes anything absent from the new tree, even when the file
        # content itself never changed). Refuse and tell the operator to
        # untrack it locally first, which is a no-op for the working tree.
        if git ls-files --error-unmatch vendor >/dev/null 2>&1 \
            && ! git diff --quiet HEAD '@{u}' -- vendor; then
            echo "!! @{u} stops tracking vendor/, but it's still tracked in this checkout." >&2
            echo "   Fast-forwarding now would DELETE vendor/ from disk." >&2
            echo "   One-time fix — untrack it locally, then merge (not fast-forward;" >&2
            echo "   both sides remove the same paths so this resolves with no conflicts" >&2
            echo "   and never touches the files on disk):" >&2
            echo "     git rm -r --cached vendor && git commit -m 'Untrack vendor/'" >&2
            echo "     git merge '@{u}'" >&2
            echo "   Then re-run this script (SKIP_PULL=1 if it's already up to date)." >&2
            exit 1
        fi

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

# --- 2. Stop if the pull brought in deps / migrations that need a human -------
if [ "$OLD_HEAD" != "$NEW_HEAD" ]; then
    if ! git diff --quiet "$OLD_HEAD" "$NEW_HEAD" -- composer.lock; then
        echo "!! composer.lock changed — run 'composer install' before continuing." >&2
    fi

    NEW_MIGRATIONS="$(git diff --name-only --diff-filter=A "$OLD_HEAD" "$NEW_HEAD" -- migrations/ | grep '\.sql$' || true)"
    if [ -n "$NEW_MIGRATIONS" ] && [ "${SKIP_MIGRATION_CHECK:-0}" != "1" ]; then
        echo "!! New migration files were pulled in:" >&2
        echo "$NEW_MIGRATIONS" | sed 's/^/     /' >&2
        echo "   Snapshot the DB first:  mysqldump ringsdb > /tmp/ringsdb_before.sql" >&2
        echo "   Apply them, then re-run with SKIP_MIGRATION_CHECK=1 to finish." >&2
        exit 1
    fi
fi

# --- 3. Clear prod cache ------------------------------------------------------
echo "==> Clearing prod cache..."
$CONSOLE cache:clear --env=prod --no-debug

# --- 4. Recompile assets ------------------------------------------------------
echo "==> Dumping assets..."
$CONSOLE assetic:dump --env=prod

# --- 5. Refresh cache/log ACLs (best-effort, no sudo) -------------------------
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

echo "==> Done."
