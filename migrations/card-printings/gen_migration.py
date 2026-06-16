#!/usr/bin/env python3
"""
Generate (and dry-run validate) the card-printings data migration from a
mysqldump. Usage:  python3 gen_migration.py ringsdb_daily.sql

- Computes a FULL dedup merge map: every (accent-normalized name, type, sphere)
  group collapses onto its earliest printing (lowest id = original release).
  The card table holds only player cards (no encounter cards), so a shared
  name+type+sphere means the same logical card reprinted. Each loser printing
  keeps any text/trait/stat differences as overrides, so rebalanced and errata
  variants are preserved rather than lost.
- Reports invariants, the losers whose rules differ from their canonical (kept
  as overrides -- worth a maintainer eyeball), and how many deck/decklist slots
  & deckchanges reference losing cards (exposure of the destructive step).
- Emits 02_migrate.sql next to this script.
"""
import sys, os, re, unicodedata
from collections import defaultdict

DUMP = sys.argv[1] if len(sys.argv) > 1 else 'ringsdb_daily.sql'
HERE = os.path.dirname(os.path.abspath(__file__))

# Repackaged products, keyed by stable pack CODE (pack ids drift like card ids did:
# the old hardcoded {61,85,98,99,100,101} pointed at an ALeP expansion (85=MotR)
# and non-existent packs in the current DB). Resolved to ids at runtime in main().
#   Starter = Two-Player Limited Edition Starter   RevCore = Revised Core (Campaign Only)
#   TSoE = The Stone of Erech   TRoB = The Ruins of Belegost   (the four "starter decks")
REPACK_PACK_CODES = {'Starter', 'RevCore', 'TSoE', 'TRoB'}
REPACK_PACKS = set()  # resolved from REPACK_PACK_CODES against the dump in main()

# Regression anchors: the global earliest-id rule must still reproduce these
# maintainer-adjudicated phase-1-2 merges (keyed by stable card CODE -> canonical
# CODE). main() asserts each still holds, so a future data shift that would change
# one of these careful choices fails loudly instead of silently. Earliest-id
# already lands on each of these canonicals; they're here as a tripwire.
CROSS_ORIGINAL_BY_CODE = {
    '502992': '02072',  # Brand son of Bain (ALeP TSoEr alt-art)   -> Hills of Emyn Muil
    '502993': '02072',  # Brand son of Bain (ALeP TSoEr rebalanced)-> Hills of Emyn Muil
    '501993': '04101',  # Glorfindel (ALeP TNaA)                   -> Foundations of Stone
    '505993': '08112',  # Galadriel (ALeP TMoG "Mirror")           -> Celebrimbor's Secret
    '503991': '131005', # Beorn (ALeP THo)                         -> Over Hill and Under Hill
    '505994': '12001',  # Denethor (ALeP TMoG)                     -> Flight of the Stormcaller
    '502991': '19084',  # Dain Ironfoot (ALeP TSoEr)               -> Ghost of Framsburg
    '500987': '21002',  # Frodo Baggins leadership (ALeP TSotS)    -> A Shadow in the East
}


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
    # Resolve repackaged packs from stable codes (pack col order: id,cycle_id,code,name,...).
    global REPACK_PACKS
    packs = load_table('pack')
    code_to_pack = {p[2]: int(p[0]) for p in packs if len(p) > 2}
    REPACK_PACKS = {code_to_pack[c] for c in REPACK_PACK_CODES if c in code_to_pack}
    missing = sorted(REPACK_PACK_CODES - set(code_to_pack))
    print("repackaged packs resolved: %s%s" % (
        sorted(REPACK_PACKS),
        ("  (codes not present in dump: %s)" % missing) if missing else ""))

    cards = load_table('card')          # id,pack_id,type_id,sphere_id,position,code,name,traits,text,...,quantity(19),...
    byid = {int(r[0]): r for r in cards if len(r) >= 20}
    bycode = defaultdict(list)
    for r in cards:
        if len(r) >= 20:
            bycode[r[5]].append(int(r[0]))

    # ---- FULL DEDUP ----------------------------------------------------------
    # Group every card by (normalized name, type, sphere); each group with >1
    # printing collapses onto its earliest printing (lowest id = original).
    T_TRAITS, T_TEXT, T_COST = 7, 8, 11   # card column indices, for flagging only
    groups = defaultdict(list)
    for r in cards:
        if len(r) < 20:
            continue
        groups[(norm(r[6]), r[2], r[3])].append(int(r[0]))

    # Classes that share name+type+sphere but are GENUINELY DIFFERENT cards, so
    # they must NOT be auto-merged (maintainer-adjudicated):
    #   - sphere 7 (Fellowship): Saga-campaign Ring-bearers / control heroes
    #     (Frodo x5, Aragorn x3) -- each printing is a distinct ability.
    #   - "(MotK)" Messenger-of-the-King variants: excluded by choice. They keep
    #     the "(MotK)" prefix so they only group among themselves (a few correct
    #     reprints left un-merged); safe to fold in later if wanted.
    SAGA_SPHERE = '7'
    def excluded(key):
        return key[2] == SAGA_SPHERE or key[0].startswith('(motk)')

    merge = {}      # loser_id -> canonical_id (earliest printing)
    flagged = []    # (loser, canonical, name): rules differ -> kept as override, review
    excluded_groups = []
    for key, ids in groups.items():
        if len(ids) < 2:
            continue
        if excluded(key):
            excluded_groups.append((key, sorted(ids)))
            continue
        canonical = min(ids)
        for lo in sorted(ids):
            if lo == canonical:
                continue
            merge[lo] = canonical
            l, c = byid[lo], byid[canonical]
            if l[T_TEXT] != c[T_TEXT] or l[T_TRAITS] != c[T_TRAITS] or l[T_COST] != c[T_COST]:
                flagged.append((lo, canonical, byid[lo][6]))

    # ---- invariants ----
    assert all(ca not in merge for ca in merge.values()), "a canonical is itself a loser (chain)"
    for ca in set(merge.values()):
        assert ca in byid, "canonical %d not found" % ca

    # regression tripwire: earliest-id must still reproduce the phase-1-2 hand
    # adjudications (Gandalf -> Core 73 and the 8 cross-original ALeP merges).
    assert merge.get(73) is None, "Gandalf Core id73 should be a canonical, not a loser"
    for lo_code, ca_code in CROSS_ORIGINAL_BY_CODE.items():
        lo_ids, ca_ids = bycode.get(lo_code, []), bycode.get(ca_code, [])
        assert len(lo_ids) == 1 and len(ca_ids) == 1, \
            "cross-original code %s/%s not uniquely present" % (lo_code, ca_code)
        assert merge.get(lo_ids[0]) == ca_ids[0], \
            "regression: code %s should still merge to %s" % (lo_code, ca_code)

    print("merge entries (losers): %d  ->  %d cards remain" % (len(merge), len(byid) - len(merge)))
    print("canonical cards gaining extra printings: %d" % len(set(merge.values())))
    print("losers whose rules differ from canonical (kept as printing overrides): %d" % len(flagged))
    for lo, ca, name in sorted(flagged)[:40]:
        print("    id%d %r [pack %s, code %s] -> canonical id%d" % (
            lo, name, byid[lo][1], byid[lo][5], ca))
    if len(flagged) > 40:
        print("    ... and %d more (full list via the override step)" % (len(flagged) - 40))
    print("EXCLUDED groups kept separate (Saga sphere-7 / MotK distinct cards): %d" % len(excluded_groups))
    for key, ids in sorted(excluded_groups):
        print("    %r (type %s, sphere %s): kept %d separate -> ids %s" % (
            byid[ids[0]][6], key[1], key[2], len(ids), ids))

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

    lines = []
    w = lines.append
    w("-- Data migration for the canonical-Card + CardPrinting refactor (FULL DEDUP).")
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
    w("-- 2) Merge map: losing card id -> canonical (earliest) card id.")
    w("CREATE TEMPORARY TABLE _merge_map (loser_id INT PRIMARY KEY, canonical_id INT NOT NULL);")
    w("INSERT INTO _merge_map (loser_id, canonical_id) VALUES")
    w("  " + map_values + ";")
    w("")
    w("-- 3) Preserve each loser printing's own rules as overrides wherever they")
    w("--    differ from the canonical card (lossless: rebalanced/errata variants keep")
    w("--    their text/stats; NULL override = use the canonical's value). Pre-repoint,")
    w("--    so cp.card_id still identifies the loser. (<=> is the null-safe equality.)")
    w("UPDATE card_printing cp")
    w("  JOIN _merge_map m ON cp.card_id = m.loser_id")
    w("  JOIN card lo ON lo.id = m.loser_id")
    w("  JOIN card ca ON ca.id = m.canonical_id")
    w("  SET cp.traits    = IF(lo.traits    <=> ca.traits,    cp.traits,    lo.traits),")
    w("      cp.text      = IF(lo.text      <=> ca.text,      cp.text,      lo.text),")
    w("      cp.cost      = IF(lo.cost      <=> ca.cost,      cp.cost,      lo.cost),")
    w("      cp.threat    = IF(lo.threat    <=> ca.threat,    cp.threat,    lo.threat),")
    w("      cp.willpower = IF(lo.willpower <=> ca.willpower, cp.willpower, lo.willpower),")
    w("      cp.attack    = IF(lo.attack    <=> ca.attack,    cp.attack,    lo.attack),")
    w("      cp.defense   = IF(lo.defense   <=> ca.defense,   cp.defense,   lo.defense),")
    w("      cp.health    = IF(lo.health    <=> ca.health,    cp.health,    lo.health),")
    w("      cp.victory   = IF(lo.victory   <=> ca.victory,   cp.victory,   lo.victory),")
    w("      cp.quest     = IF(lo.quest     <=> ca.quest,     cp.quest,     lo.quest);")
    w("")
    w("-- 4) Repoint the losers' printings onto the canonical card.")
    w("UPDATE card_printing cp JOIN _merge_map m ON cp.card_id = m.loser_id SET cp.card_id = m.canonical_id;")
    w("")
    w("-- 5) Repoint every saved reference (FKs to card.id).")
    for tbl in ('deckslot', 'decklistslot', 'decksideslot', 'decklistsideslot', 'review'):
        w("UPDATE %s s JOIN _merge_map m ON s.card_id = m.loser_id SET s.card_id = m.canonical_id;" % tbl)
    w("")
    w("-- NOTE: deck-edit history (deckchange.variation) stores card CODES in JSON and")
    w("--   is intentionally NOT rewritten here -- the loser codes it references become")
    w("--   unresolved in the history view only (deck CONTENTS via *_slot are repointed")
    w("--   above and stay correct). Rewriting needs widening variation past varchar(1024)")
    w("--   first; left to a follow-up so this migration stays scoped to the card data.")
    w("")
    w("-- 6) Collapse duplicate printings now sharing the same canonical card AND pack")
    w("--    (two same-named cards in one pack, or Gandalf x2 in the Starter): keep one,")
    w("--    sum quantity. Scans all packs since dedup can surface within-pack dupes anywhere.")
    w("UPDATE card_printing cp JOIN (")
    w("    SELECT MIN(id) keep_id, SUM(quantity) tq FROM card_printing")
    w("    GROUP BY card_id, pack_id HAVING COUNT(*) > 1")
    w("  ) d ON cp.id = d.keep_id SET cp.quantity = d.tq;")
    w("DELETE cp FROM card_printing cp JOIN (")
    w("    SELECT card_id, pack_id, MIN(id) keep_id FROM card_printing")
    w("    GROUP BY card_id, pack_id HAVING COUNT(*) > 1")
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
