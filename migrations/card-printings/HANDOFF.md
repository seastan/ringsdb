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

> **Merge rule (current):** hybrid of "known reprints" + rules-equivalence.
> Cards in the repackaged cycles (Starter / RevCore / StarterDecks) merge onto
> the matching original; among other cards, only those with IDENTICAL rules
> merge. Genuinely-different same-named cards (Gandalf Core vs Over Hill, the
> saga Treasure cards, the rebalanced ALeP Brand, saga Ring-bearers) STAY
> SEPARATE. This supersedes the earlier "full dedup" approach.
>
> **If you already applied a previous `02_migrate.sql`** (it deletes rows, so it
> is not re-runnable over itself): restore the DB from your pre-migration
> snapshot first, then re-run from a fresh regenerate (section 2) so the new
> rule applies cleanly.

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

## 4. Verify

Card ids drift between DBs, so verify by stable CODE. The generator's stdout is
the source of truth for counts (it prints "N cards remain", the repackaged packs,
and the names kept separate) — compare the queries below against that.

```sql
-- Core Gandalf (code 01073) gained the repackaged reprints as extra printings
-- (Core + Two-Player Starter + the starter decks it appears in); Starter qty summed.
SELECT p.code, cp.quantity, cp.image_code
FROM card c JOIN card_printing cp ON cp.card_id = c.id JOIN pack p ON p.id = cp.pack_id
WHERE c.code = '01073' ORDER BY p.code;

-- ...but Over Hill Gandalf (code 131010) is a DIFFERENT card and must STILL EXIST
-- as its own row (different ability -> not merged). Expect exactly 1 row.
SELECT id, code, name FROM card WHERE code = '131010';

-- Every card has >=1 printing (expect 0 rows)
SELECT c.id, c.name FROM card c
LEFT JOIN card_printing cp ON cp.card_id = c.id WHERE cp.id IS NULL;

-- Nothing points at a deleted card (all expect 0)
SELECT COUNT(*) FROM card_printing cp LEFT JOIN card c ON c.id=cp.card_id WHERE c.id IS NULL;
SELECT COUNT(*) FROM decklistsideslot s LEFT JOIN card c ON c.id=s.card_id WHERE c.id IS NULL;
SELECT COUNT(*) FROM deckslot s LEFT JOIN card c ON c.id=s.card_id WHERE c.id IS NULL;

-- Repackaged packs flagged: the 6 packs in cycles Starter/RevCore/StarterDecks
SELECT pk.id, pk.code, pk.name FROM pack pk WHERE pk.is_repackaged = 1;

-- No remaining DUPLICATE codes (each logical card is one row). Expect 0.
SELECT code, COUNT(*) FROM card GROUP BY code HAVING COUNT(*) > 1;

-- Card count after merge — compare to the generator's "cards remain" line.
SELECT COUNT(*) FROM card;
```

Also smoke-test the app: load the deckbuilder and confirm each card appears once
(e.g. one "Gandalf" neutral ally entry for Core, a separate one for Over Hill);
open an existing saved deck and confirm it loads unchanged (decks resolve by card
`code`, which the canonical cards retain).

## 5. Report back

Reply with: the `doctrine:schema:update --dump-sql` output, whether you
regenerated `02_migrate.sql`, the result of each verification query, any errors,
and the app smoke-test result. Do **not** touch production. The dev-side Claude
will use this to proceed with phases 3–9.
