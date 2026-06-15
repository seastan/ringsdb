#!/usr/bin/env python3
"""
Generate (and dry-run validate) the card-printings data migration from a
mysqldump. Usage:  python3 gen_migration.py ringsdb_daily.sql

- Computes the loser->canonical merge map (normalized name+type+sphere for the
  6 repackaged packs + explicit overrides + cross-original ALeP merges).
- Reports invariants and how many deck/decklist slots & deckchanges reference
  losing cards (exposure of the destructive step).
- Emits 02_migrate.sql next to this script.
"""
import sys, os, re, unicodedata
from collections import defaultdict

DUMP = sys.argv[1] if len(sys.argv) > 1 else 'ringsdb_daily.sql'
HERE = os.path.dirname(os.path.abspath(__file__))

REPACK_PACKS = {61, 85, 98, 99, 100, 101}

# When a repackaged card's normalized key matches >1 original, force the canonical:
#   keyed by normalized name -> canonical card id
REPACK_OVERRIDES = {
    'gandalf': 73,     # Core ally, not Over Hill (id459)
    'galadriel': 340,  # Celebrimbor's Secret hero, not ALeP Mirror (id1631)
}

# Cross-original merges (losing card id -> canonical id), maintainer-adjudicated.
CROSS_ORIGINAL = {
    1460: 104,  # Brand son of Bain (ALeP alt-art) -> Hills of Emyn Muil
    1461: 104,  # Brand son of Bain (ALeP rebalanced) -> Hills of Emyn Muil
    1457: 177,  # Glorfindel (ALeP) -> Foundations of Stone
    1631: 340,  # Galadriel (ALeP Mirror) -> Celebrimbor's Secret
    1583: 454,  # Beorn (ALeP) -> Over Hill and Under Hill
    1632: 549,  # Denethor (ALeP) -> Flight of the Stormcaller
    1459: 907,  # Dain Ironfoot (ALeP) -> Ghost of Framsburg
    1458: 938,  # Frodo Baggins leadership (ALeP) -> A Shadow in the East
}

# Losing cards whose differing text/traits/stats must be preserved as printing
# overrides (rebalanced variants, not mere alt-art).
REBALANCED_LOSERS = {1461}


def norm(s):
    s = unicodedata.normalize('NFKD', s)
    s = ''.join(c for c in s if not unicodedata.combining(c))
    return s.lower().strip()


def parse_values(s):
    rows = []; i = 0; n = len(s)
    while i < n:
        if s[i] != '(':
            i += 1; continue
        i += 1; fields = []; cur = []; instr = False
        while i < n:
            c = s[i]
            if instr:
                if c == '\\':
                    cur.append(s[i + 1]); i += 2; continue
                if c == "'":
                    instr = False; i += 1; continue
                cur.append(c); i += 1; continue
            else:
                if c == "'":
                    instr = True; i += 1; continue
                if c == ',':
                    fields.append(''.join(cur)); cur = []; i += 1; continue
                if c == ')':
                    fields.append(''.join(cur)); i += 1; break
                cur.append(c); i += 1; continue
        rows.append(fields)
        while i < n and s[i] != '(' and s[i] != ';':
            i += 1
    return rows


def load_table(name):
    """Return list of value-tuples for the (single-statement) INSERT of `name`."""
    needle = 'INSERT INTO `%s` VALUES' % name
    out = []
    with open(DUMP, encoding='utf-8', errors='replace') as f:
        for line in f:
            if line.startswith(needle):
                out = parse_values(line[line.index('VALUES') + 6:].rstrip().rstrip(';'))
                break
    return out


def main():
    cards = load_table('card')          # id,pack_id,type_id,sphere_id,position,code,name,traits,text,...,quantity(19),...
    byid = {int(r[0]): r for r in cards if len(r) >= 20}

    # canonical lookup from NON-repackaged cards
    canon = defaultdict(list)
    for r in cards:
        if len(r) < 20:
            continue
        if int(r[1]) in REPACK_PACKS:
            continue
        canon[(norm(r[6]), r[2], r[3])].append(int(r[0]))

    merge = {}   # loser_id -> canonical_id
    new_cards = []  # repackaged cards with no canonical (stay as own canonical)

    for r in cards:
        if len(r) < 20:
            continue
        cid = int(r[0])
        if int(r[1]) not in REPACK_PACKS:
            continue
        matches = canon.get((norm(r[6]), r[2], r[3]), [])
        if len(matches) == 0:
            new_cards.append(cid)
        elif len(matches) == 1:
            merge[cid] = matches[0]
        else:
            ov = REPACK_OVERRIDES.get(norm(r[6]))
            assert ov is not None, "Unhandled ambiguity for %r (matches %s)" % (r[6], matches)
            merge[cid] = ov

    # explicit cross-original merges
    for lo, ca in CROSS_ORIGINAL.items():
        merge[lo] = ca

    # ---- invariants ----
    assert all(ca not in merge for ca in merge.values()), "a canonical is itself a loser (chain)"
    for ca in set(merge.values()):
        assert ca in byid, "canonical %d not found" % ca

    print("merge entries (losers): %d" % len(merge))
    print("repackaged 'new' cards (kept canonical): %d -> %s" % (
        len(new_cards), sorted(new_cards)))

    # ---- exposure: how many slots / changes reference losers ----
    losers = set(merge)
    for tbl, cardcol in [('deckslot', 2), ('decklistslot', 2),
                         ('decksideslot', 2), ('decklistsideslot', 2),
                         ('review', None)]:
        rows = load_table(tbl)
        if not rows:
            print("  %s: (no rows parsed)" % tbl); continue
        # detect card_id column index by looking for an int col matching ids
        # deck*slot layout: (id, <container>_id, card_id, quantity)
        idx = cardcol if cardcol is not None else None
        if idx is None:
            # review: find the card_id column heuristically (col with values in byid)
            idx = 1
            for j in range(1, min(6, len(rows[0]))):
                vals = [r[j] for r in rows[:50] if r[j].isdigit()]
                if vals and all(int(v) in byid for v in vals):
                    idx = j; break
        cnt = sum(1 for r in rows if len(r) > idx and r[idx].isdigit() and int(r[idx]) in losers)
        print("  %s: %d rows reference a losing card" % (tbl, cnt))

    # deckchange.variation references loser CODES
    loser_codes = {byid[l][5] for l in losers}
    dc = load_table('deckchange')
    if dc:
        # variation is the varchar col; find it (contains a brace/quote-ish json)
        vidx = 3
        hit = 0
        for r in dc:
            if len(r) > vidx and any(code in r[vidx] for code in loser_codes):
                hit += 1
        print("  deckchange: %d variations reference a losing card CODE (of %d)" % (hit, len(dc)))

    # sample of the merge map for eyeballing
    print("\nsample merges (loser code/pack -> canonical code):")
    for lo in list(sorted(losers))[:12]:
        ca = merge[lo]
        print("  %s id%d [pack %s] -> %s id%d" % (
            byid[lo][6], lo, byid[lo][1], byid[ca][5], ca))

    emit_sql(merge, byid)
    print("\nwrote %s" % os.path.join(HERE, '02_migrate.sql'))


def emit_sql(merge, byid):
    packs = ','.join(str(p) for p in sorted(REPACK_PACKS))
    canon_ids = ','.join(str(c) for c in sorted(set(merge.values())))
    map_values = ',\n  '.join('(%d,%d)' % (lo, ca) for lo, ca in sorted(merge.items()))
    reb = ','.join(str(l) for l in sorted(REBALANCED_LOSERS))

    lines = []
    w = lines.append
    w("-- Phase 2: data migration for the canonical-Card + CardPrinting refactor.")
    w("-- GENERATED by gen_migration.py from a prod dump. Run AFTER 01_schema.sql,")
    w("-- on a DB snapshot first. Transaction-wrapped; safe to re-run on the same DB.")
    w("")
    w("START TRANSACTION;")
    w("")
    w("-- 1) One printing per existing card (idempotent via NOT EXISTS).")
    w("INSERT INTO card_printing")
    w("    (card_id, pack_id, position, quantity, image_code, illustrator, octgnid, date_creation, date_update)")
    w("  SELECT c.id, c.pack_id, c.position, c.quantity, c.code, c.illustrator, c.octgnid, c.date_creation, c.date_update")
    w("  FROM card c")
    w("  WHERE NOT EXISTS (SELECT 1 FROM card_printing cp WHERE cp.card_id = c.id AND cp.pack_id = c.pack_id);")
    w("")
    w("-- 2) Merge map: losing card id -> canonical card id.")
    w("CREATE TEMPORARY TABLE _merge_map (loser_id INT PRIMARY KEY, canonical_id INT NOT NULL);")
    w("INSERT INTO _merge_map (loser_id, canonical_id) VALUES")
    w("  " + map_values + ";")
    w("")
    w("-- 3) Preserve rebalanced losers' distinct rules as printing overrides (pre-repoint).")
    if reb:
        w("UPDATE card_printing cp JOIN card c ON cp.card_id = c.id")
        w("  SET cp.text = c.text, cp.traits = c.traits, cp.cost = c.cost, cp.threat = c.threat,")
        w("      cp.willpower = c.willpower, cp.attack = c.attack, cp.defense = c.defense,")
        w("      cp.health = c.health, cp.victory = c.victory, cp.quest = c.quest")
        w("  WHERE c.id IN (%s);" % reb)
    w("")
    w("-- 4) Repoint the losers' printings onto the canonical card.")
    w("UPDATE card_printing cp JOIN _merge_map m ON cp.card_id = m.loser_id SET cp.card_id = m.canonical_id;")
    w("")
    w("-- 5) Repoint every saved reference (FKs to card.id). Most are 0 rows; harmless.")
    for tbl in ('deckslot', 'decklistslot', 'decksideslot', 'decklistsideslot', 'review'):
        w("UPDATE %s s JOIN _merge_map m ON s.card_id = m.loser_id SET s.card_id = m.canonical_id;" % tbl)
    w("")
    w("-- 6) Collapse identical-art duplicate printings within a repackaged pack")
    w("--    (only case: Gandalf x2 in pack 61 -> one printing, quantity summed).")
    w("UPDATE card_printing cp JOIN (")
    w("    SELECT MIN(id) keep_id, SUM(quantity) tq FROM card_printing")
    w("    WHERE pack_id IN (%s) GROUP BY card_id, pack_id HAVING COUNT(*) > 1" % packs)
    w("  ) d ON cp.id = d.keep_id SET cp.quantity = d.tq;")
    w("DELETE cp FROM card_printing cp JOIN (")
    w("    SELECT card_id, pack_id, MIN(id) keep_id FROM card_printing")
    w("    WHERE pack_id IN (%s) GROUP BY card_id, pack_id HAVING COUNT(*) > 1" % packs)
    w("  ) d ON cp.card_id = d.card_id AND cp.pack_id = d.pack_id AND cp.id <> d.keep_id;")
    w("")
    w("-- 7) Dedup any slot that now has two rows for the same canonical card (sum qty).")
    for tbl, container in (('deckslot', 'deck_id'), ('decklistslot', 'decklist_id'),
                           ('decksideslot', 'deck_id'), ('decklistsideslot', 'decklist_id')):
        w("UPDATE %s s JOIN (" % tbl)
        w("    SELECT MIN(id) keep_id, SUM(quantity) tq FROM %s" % tbl)
        w("    WHERE card_id IN (%s) GROUP BY %s, card_id HAVING COUNT(*) > 1" % (canon_ids, container))
        w("  ) d ON s.id = d.keep_id SET s.quantity = d.tq;")
        w("DELETE s FROM %s s JOIN (" % tbl)
        w("    SELECT %s AS cont, card_id, MIN(id) keep_id FROM %s" % (container, tbl))
        w("    WHERE card_id IN (%s) GROUP BY %s, card_id HAVING COUNT(*) > 1" % (canon_ids, container))
        w("  ) d ON s.%s = d.cont AND s.card_id = d.card_id AND s.id <> d.keep_id;" % container)
    w("")
    w("-- 8) Delete the now-merged loser card rows.")
    w("DELETE c FROM card c JOIN _merge_map m ON c.id = m.loser_id;")
    w("")
    w("-- 9) Flag the repackaged products (collection page groups them separately).")
    w("UPDATE pack SET is_repackaged = 1 WHERE id IN (%s);" % packs)
    w("")
    w("DROP TEMPORARY TABLE _merge_map;")
    w("COMMIT;")
    w("")

    with open(os.path.join(HERE, '02_migrate.sql'), 'w') as f:
        f.write('\n'.join(lines))


if __name__ == '__main__':
    main()
