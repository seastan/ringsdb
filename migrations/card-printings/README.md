# Card-printings migration

SQL + tooling for the canonical-`Card` + `card_printing` refactor (see the
project plan). Designed to be applied with **MySQL alone** — no app/PHP needed.

## Run order (on a DB **snapshot** first)

1. `01_schema.sql` — additive: creates `card_printing`, adds `pack.is_repackaged`.
   App keeps running with the old `card.pack_id` still populated.
2. `02_migrate.sql` — data: backfills one printing per card, merges duplicate
   reprints onto canonical cards (repointing all `*_slot`/`review` FKs),
   collapses the lone within-pack duplicate (Gandalf ×2 in the Two-Player
   Starter → qty 4), deletes merged loser rows, flags the 6 repackaged packs.
   Transaction-wrapped.

`card.pack_id` and the printing-level columns on `card` are dropped only in the
final cleanup phase, after all reads move to printings.

## Regenerating / re-validating

`02_migrate.sql` is **generated** from a fresh prod dump so the merge map uses
current card ids:

```
python3 gen_migration.py /path/to/ringsdb_daily.sql
```

This also prints invariants and the exposure of the destructive step. Validated
against the dump: 1515 cards → 1314 (201 merged), 1514 printings, every card
keeps ≥1 printing, 0 orphaned printings/slots, only 232 decklist-sideboard slots
repointed, 0 deckchange history entries affected.

## Merge rules (encoded in `gen_migration.py`)

- 6 repackaged packs (`61` Two-Player Starter, `85` Revised Core campaign-only,
  `98/99/100/101` starter decks) → map each card to the original with the same
  **accent-normalized name + type + sphere**.
- Ambiguity overrides: Gandalf → Core `id 73`; Galadriel → Celebrimbor's `id 340`.
- 7 Revised-Core campaign-only cards have no original → stay canonical.
- 8 cross-original ALeP variants merged to their official card (Brand 1460+1461,
  Glorfindel, Galadriel, Beorn, Denethor, Dáin, Frodo-leadership); Brand 1461 is
  a *rebalanced* variant whose text/stats are kept as printing overrides.
