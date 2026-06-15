# Handoff — apply & verify the card-printings migration (server side)

You are a Claude running on a RingsDB **server with a live MySQL DB and the PHP
app** (Symfony 2.7). A teammate developed the canonical-`Card` + `card_printing`
refactor on a machine with no DB; your job is to **apply the schema + data
migration on a snapshot, verify it, and report back**. Do **not** run it against
production until the test snapshot checks out.

## What this branch delivers so far (phases 1–2 only)

- New `CardPrinting` entity/table linking a card to a pack (per-pack `quantity`,
  `image_code`, nullable text/traits/stat overrides). `Card` keeps its old
  `pack` association **for now**; printing-level columns on `card` are dropped
  only in a later cleanup phase.
- `migrations/card-printings/01_schema.sql` — additive DDL.
- `migrations/card-printings/02_migrate.sql` — data migration (generated).
- `gen_migration.py` — regenerates/validates `02_migrate.sql` from a dump.

**Not yet implemented (phases 3–9):** backend reads via printings, API `packs[]`,
frontend dedup/ownership, collection UX, quantity-aware search, art toggle. So
after migrating, expect: deckbuilder duplicates are **gone**, but the 6
repackaged packs will look **empty** in the app (their cards were merged into the
canonical cards in the original packs, and the app still reads `card.pack_id`
until phase 3). The app should otherwise run normally. This is expected.

## 0. Branch & snapshot

The test server tracks `ringsdb_test`, which has been brought up to date with
`master`. The refactor lives on `feature/card-printings` (master-based, +2
commits). Bring it into the working tree without leaving `ringsdb_test`:

```
git fetch origin
git merge --no-edit origin/feature/card-printings   # clean: just adds the refactor commits
# (or `git checkout feature/card-printings` if you prefer to test off the branch directly)

# Take a restorable snapshot of the TEST database FIRST:
mysqldump <testdb> > /tmp/ringsdb_test_backup.sql
```

After merging, run `composer install` only if dependencies changed (they did not)
and `php app/console cache:clear` so Doctrine sees the new entity.

## 1. Apply schema (`card_printing` table + `pack.is_repackaged`)

The entities/ORM are committed, so Doctrine can also generate this. Preview what
Doctrine expects and confirm it matches `01_schema.sql`:

```
php app/console doctrine:schema:update --dump-sql        # should show the new table + column
```

Then apply EITHER (pick one):
- `mysql <testdb> < migrations/card-printings/01_schema.sql`, or
- `php app/console doctrine:schema:update --force`

Afterward clear caches: `php app/console cache:clear --env=prod` (and `dev`).

## 2. (Optional) Regenerate the data migration from the CURRENT db

`02_migrate.sql` was generated from a dump dated when development started. If the
card data changed since, regenerate so the merge map uses current card ids:

```
mysqldump <testdb> > /tmp/current.sql
python3 migrations/card-printings/gen_migration.py /tmp/current.sql
```

It prints invariants + exposure and rewrites `02_migrate.sql`. If card ids are
unchanged, the file will be identical and you can skip this.

## 3. Apply the data migration

```
mysql <testdb> < migrations/card-printings/02_migrate.sql
php app/console cache:clear --env=prod
```

It is transaction-wrapped. If it errors, the transaction rolls back; capture the
exact error and report it.

## 4. Verify (expected values from the dev dump — adjust if you regenerated)

```sql
-- Gandalf (canonical id 73) now spans Core + the repackaged packs; pack 61 = qty 4
SELECT cp.pack_id, p.code, cp.quantity, cp.image_code
FROM card_printing cp JOIN pack p ON p.id = cp.pack_id
WHERE cp.card_id = 73 ORDER BY cp.pack_id;
--   expect packs 1, 61(qty 4), 98, 99, 100, 101

-- Every card has at least one printing (expect 0 rows)
SELECT c.id, c.name FROM card c
LEFT JOIN card_printing cp ON cp.card_id = c.id WHERE cp.id IS NULL;

-- No printing or slot points at a deleted card (all expect 0)
SELECT COUNT(*) FROM card_printing cp LEFT JOIN card c ON c.id=cp.card_id WHERE c.id IS NULL;
SELECT COUNT(*) FROM decklistsideslot s LEFT JOIN card c ON c.id=s.card_id WHERE c.id IS NULL;
SELECT COUNT(*) FROM deckslot s LEFT JOIN card c ON c.id=s.card_id WHERE c.id IS NULL;

-- Repackaged packs flagged (expect 6 rows: 61,85,98,99,100,101)
SELECT id, code, name, is_repackaged FROM pack WHERE is_repackaged = 1;

-- Card count dropped by the number of merged duplicates (dev dump: 1515 -> 1314)
SELECT COUNT(*) FROM card;

-- Brand son of Bain (canonical 104) keeps TWO printings in pack 107
-- (alt-art + rebalanced); the rebalanced one has override text populated
SELECT id, pack_id, image_code, (text IS NOT NULL) AS has_text_override
FROM card_printing WHERE card_id = 104 ORDER BY pack_id, id;
```

Also smoke-test the app: load the deckbuilder and confirm each card appears once;
open an existing saved deck and confirm it loads unchanged (decks resolve by card
`code`, which the canonical cards retain).

## 5. Report back

Reply with: the `doctrine:schema:update --dump-sql` output, whether you
regenerated `02_migrate.sql`, the result of each verification query, any errors,
and the app smoke-test result. Do **not** touch production. The dev-side Claude
will use this to proceed with phases 3–9.
