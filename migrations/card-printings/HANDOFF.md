# Handoff — deploy the card-printings refactor to the server

You are a Claude running on a RingsDB **server with a live MySQL DB and the PHP
app** (Symfony 2.7). The card-printings refactor is fully developed and merged
to `master`. Your job is to **apply the DB migrations on a snapshot, verify,
and then apply to production**.

## What this refactor does

Replaces the old model (each `Card` belongs to exactly one `Pack`) with a
`CardPrinting` join entity so one canonical `Card` can appear in many packs.
This fixes: deckbuilder duplicates from repackaged products, collection-page
inability to own the Revised Core Set / starter decks, and deck-search
wrongly excluding decks when the used card is available in an allowed pack.

New features: per-pack quantity inputs on the collection page, quantity-aware
deck search, alt-art selector on card pages and the modal, Gandalf ally
disambiguated as `Gandalf (Core)` / `Gandalf (OHaUH)` in typeaheads.

## Migration files (run in order)

All four are in `migrations/card-printings/`:

| File | What it does |
|------|-------------|
| `01_schema.sql` | Creates `card_printing` table; adds `is_repackaged` + `date_release` to `pack` |
| `02_migrate.sql` | Backfills one printing per existing card; merges repackaged-pack duplicates onto canonical cards; repoints deck/decklist slots; deletes duplicate card rows; flags the 6 repackaged packs |
| `03_user_art_preferences.sql` | Adds `art_preferences` TEXT column to `user` table |
| `04_cleanup.sql` | Drops `card.pack_id` FK, `card.quantity`, `card.illustrator`, `card.octgnid` columns (the app no longer reads them) |

**`04_cleanup.sql` is the only irreversible step.** Run 01–03 first, verify,
then run 04.

---

## Step 0 — Pull and snapshot

```bash
cd /var/www/html          # or wherever the app lives
git pull origin master

# Snapshot the DB before touching it
mysqldump <db_name> > /tmp/ringsdb_before_migration.sql
```

No `composer install` needed (no dependency changes).

---

## Step 1 — Apply schema (01_schema.sql)

```bash
mysql <db_name> < migrations/card-printings/01_schema.sql
php app/console doctrine:schema:update --dump-sql   # should show "Nothing to update"
php app/console cache:clear --env=prod --no-debug
chown -R www-data:www-data app/cache app/logs
```

If `doctrine:schema:update --dump-sql` shows remaining diffs, apply them with
`--force` so Doctrine and the DB are in sync before proceeding.

---

## Step 2 — (Recommended) Regenerate the data migration

`02_migrate.sql` was generated from a dump taken during development. If card
data has changed since, regenerate it so the merge map uses current card IDs:

```bash
mysqldump <db_name> > /tmp/current.sql
python3 migrations/card-printings/gen_migration.py /tmp/current.sql
```

The script prints invariants (card counts, packs flagged, names kept separate)
and rewrites `02_migrate.sql`. If nothing changed the output file will be
identical. Run this step; it is safe.

---

## Step 3 — Apply data migration (02_migrate.sql)

```bash
mysql <db_name> < migrations/card-printings/02_migrate.sql
```

The file is transaction-wrapped. If it errors, the transaction rolls back.
Capture the full error and stop — do not proceed to step 4.

---

## Step 4 — Verify data

Run these SQL queries. Expected results are in the comments.

```sql
-- 1. Core Gandalf (code 01073) has multiple printings (Core + repackaged packs)
SELECT p.code, cp.quantity, cp.image_code
FROM card c
JOIN card_printing cp ON cp.card_id = c.id
JOIN pack p ON p.id = cp.pack_id
WHERE c.code = '01073'
ORDER BY p.code;
-- Expect several rows: Core plus any repackaged packs that include Gandalf.

-- 2. Over Hill Gandalf MUST still exist as its own canonical card
SELECT id, code, name FROM card WHERE code = '131010';
-- Expect exactly 1 row (different ability, kept separate).

-- 3. Every card has at least one printing (expect 0 rows)
SELECT c.id, c.code, c.name
FROM card c
LEFT JOIN card_printing cp ON cp.card_id = c.id
WHERE cp.id IS NULL;

-- 4. No orphaned foreign keys anywhere (all expect 0)
SELECT COUNT(*) FROM card_printing cp LEFT JOIN card c ON c.id = cp.card_id WHERE c.id IS NULL;
SELECT COUNT(*) FROM deckslot s LEFT JOIN card c ON c.id = s.card_id WHERE c.id IS NULL;
SELECT COUNT(*) FROM decklistslot s LEFT JOIN card c ON c.id = s.card_id WHERE c.id IS NULL;
SELECT COUNT(*) FROM decklistsideslot s LEFT JOIN card c ON c.id = s.card_id WHERE c.id IS NULL;

-- 5. The 6 repackaged packs are flagged
SELECT id, code, name FROM pack WHERE is_repackaged = 1;
-- Expect: Two-Player Starter (61), Revised Core Set (85), and the 4 starter
-- decks (98-101) — exact IDs may differ; names are the key check.

-- 6. No duplicate card codes (each logical card is one row, expect 0)
SELECT code, COUNT(*) n FROM card GROUP BY code HAVING n > 1;

-- 7. Card count — compare to gen_migration.py's "cards remain" output
SELECT COUNT(*) FROM card;
```

If any check fails, restore from `/tmp/ringsdb_before_migration.sql` and report.

---

## Step 5 — Apply art preferences migration (03_user_art_preferences.sql)

```bash
mysql <db_name> < migrations/card-printings/03_user_art_preferences.sql
```

Adds `art_preferences` TEXT column to `user`. Safe to run even if the column
already exists (it checks first).

---

## Step 6 — Apply cleanup (04_cleanup.sql)

**Only run this after steps 1–5 all pass.**

```bash
mysql <db_name> < migrations/card-printings/04_cleanup.sql
```

This dynamically finds and drops the `card.pack_id` foreign key, then drops
`card.pack_id`, `card.quantity`, `card.illustrator`, `card.octgnid`. The app
no longer reads these columns. After this step there is no clean rollback
short of restoring the full snapshot.

```bash
php app/console cache:clear --env=prod --no-debug
chown -R www-data:www-data app/cache app/logs
```

---

## Step 7 — App smoke test

Check these in a browser (or curl) after clearing cache:

1. **Deckbuilder** — each logical card appears once. Search for "Gandalf"; expect
   two neutral ally entries: `Gandalf (Core)` and `Gandalf (OHaUH)`.
2. **Collection page** — shows a "Repackaged" section with the 6 repackaged
   products; each pack has a numeric count input (not a checkbox).
3. **API** — `GET /api/public/cards/` — Aragorn (code `01001`) should have
   `pack_code: "Core"` plus a `packs[]` array listing Core and any repackaged
   packs that include him.
4. **Card page** — visit any card. If logged in with owned packs, expect a
   "Printing (owned)" section showing owned-copy counts. For Two-Player Starter
   cards with unique art, expect "Art / printing (owned)" with a clickable
   art-switcher.
5. **Deck search** — search for decklists with "Allowed packs" = Revised Core
   Set; expect to find decklists that use Core Set versions of the same cards.
6. **Quest log page** — `/myquestlogs` should load without error.
7. **Card tooltip** (hover over a card in the deckbuilder) — should show card
   name, type, text, sphere — no "undefined" set label.

---

## What to report back

- Output of `doctrine:schema:update --dump-sql` after step 1
- Whether you regenerated `02_migrate.sql` and any diff summary from the script
- Results of each verification query in step 4
- Any errors from steps 3, 5, 6
- Smoke test results for each item in step 7
- Whether you applied to production or stopped at the test snapshot
