#!/usr/bin/env python3
"""
Generate (and dry-run validate) the card-printings data migration from a
mysqldump. Usage:  python3 gen_migration.py ringsdb_daily.sql

Merge philosophy: hybrid of "known reprints" + rules-equivalence.
- REPACKAGED products (cycles Starter / RevCore / StarterDecks = the Two-Player
  Starter, Revised Core, and 4 starter decks) only ever REPRINT existing cards,
  so each of their cards merges onto the matching original (same name+type+
  sphere) regardless of typos/formatting/errata. When a name matches >1 original
  (Gandalf Core vs Over Hill), the reprint is attached to the original whose
  rules it shares (else a curated override).
- Among NON-repackaged cards, only cards with IDENTICAL normalized rules merge
  (true reprints/alt-art); cards sharing a name but with different rules (Gandalf
  Core vs Over Hill, saga Treasures, rebalanced ALeP variants, saga Ring-bearers)
  stay SEPARATE. A small curated FORCE_MERGE list handles adjudicated alt-arts
  whose text was only editorially reworded (Galadriel "his"->"their").
- Printings differ only by ART (image_code/illustrator/octgnid) + pack/quantity;
  no rules overrides are written.
- Reports repackaged packs, names kept as multiple distinct cards (eyeball), and
  slot/deckchange exposure. Emits 02_migrate.sql next to this script.
"""
import sys, os, re, unicodedata
from collections import defaultdict

DUMP = sys.argv[1] if len(sys.argv) > 1 else 'ringsdb_daily.sql'
HERE = os.path.dirname(os.path.abspath(__file__))

# Repackaged products are identified by CYCLE code (more stable than pack codes):
#   Starter      = Two-Player Limited Edition Starter (1 pack)
#   RevCore      = Revised Core Set, Campaign Only (1 pack)
#   StarterDecks = the four starter decks (Dwarves of Durin, Elves of Lorien,
#                  Defenders of Gondor, Riders of Rohan)
REPACK_CYCLE_CODES = {'Starter', 'RevCore', 'StarterDecks'}
REPACK_PACKS = set()  # resolved against the dump in main()

# Reprints whose name matches >1 original AND whose rules don't cleanly pick one
# (data typo etc.): force the reprint (loser CODE) onto a chosen original CODE.
REPACK_OVERRIDE_BY_CODE = {}  # populated only if disambiguation reports an ambiguity

# Maintainer-adjudicated alt-arts that the rules-signature rule misses because
# their text was only editorially reworded (same card). loser CODE -> canonical
# CODE. Brand's *rebalanced* ALeP printing (502993) is deliberately NOT here --
# different rules => stays a separate card.
FORCE_MERGE_BY_CODE = {
    '505993': '08112',  # Galadriel (ALeP "Mirror", his->their) -> Celebrimbor's Secret
    '502992': '02072',  # Brand son of Bain (ALeP alt-art)      -> Hills of Emyn Muil
    '501993': '04101',  # Glorfindel (ALeP)                     -> Foundations of Stone
    '503991': '131005', # Beorn (ALeP)                          -> Over Hill and Under Hill
    '505994': '12001',  # Denethor (ALeP)                       -> Flight of the Stormcaller
    '502991': '19084',  # Dain Ironfoot (ALeP)                  -> Ghost of Framsburg
    '500987': '21002',  # Frodo Baggins leadership (ALeP)       -> A Shadow in the East
}

# Tripwires: cards that MUST stay separate (different rules sharing a name).
# Keyed by code; main() asserts neither becomes a loser of the other.
KEEP_SEPARATE_CODES = [
    ('01073', '131010'),  # Gandalf: Core ally vs Over Hill ally (different ability)
    ('02072', '502993'),  # Brand son of Bain: original vs ALeP *rebalanced*
]

# card column indices
C_ID, C_PACK, C_TYPE, C_SPHERE, C_CODE, C_NAME, C_TRAITS, C_TEXT = 0, 1, 2, 3, 5, 6, 7, 8
C_STATS = slice(11, 19)  # cost,threat,willpower,attack,defense,health,victory,quest
C_QTY = 19

_ESC = {'n': '\n', 'r': '\r', 't': '\t', '0': '\0', 'b': '\b', 'Z': '\x1a'}


def norm_name(s):
    s = unicodedata.normalize('NFKD', s)
    s = ''.join(c for c in s if not unicodedata.combining(c))
    return s.lower().strip()


def _nz(v):
    """The dump renders SQL NULL as the literal 'NULL'; treat it (and None) as empty."""
    return '' if v is None or v == 'NULL' else v


def norm_rules(s):
    """Aggressive normalization: strip tags, accents, punctuation, whitespace."""
    s = _nz(s)
    s = unicodedata.normalize('NFKD', s)
    s = ''.join(c for c in s if not unicodedata.combining(c)).lower()
    s = re.sub(r'<[^>]+>', ' ', s)        # drop html-ish tags
    s = re.sub(r'[^a-z0-9]+', ' ', s)     # drop punctuation/quotes/dashes/newlines
    return ' '.join(s.split()).strip()


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
                    cur.append(_ESC.get(s[i + 1], s[i + 1])); i += 2; continue
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
    needle = 'INSERT INTO `%s` VALUES' % name
    with open(DUMP, encoding='utf-8', errors='replace') as f:
        for line in f:
            if line.startswith(needle):
                return parse_values(line[line.index('VALUES') + 6:].rstrip().rstrip(';'))
    return []


def main():
    global REPACK_PACKS
    cycles = {int(c[0]): c[1] for c in load_table('cycle')}   # id -> code
    packs = load_table('pack')
    REPACK_PACKS = {int(p[0]) for p in packs
                    if len(p) > 1 and cycles.get(int(p[1])) in REPACK_CYCLE_CODES}
    print("repackaged packs (cycles %s): %s" % (
        sorted(REPACK_CYCLE_CODES),
        sorted((int(p[0]), p[2]) for p in packs if int(p[0]) in REPACK_PACKS)))

    cards = load_table('card')
    byid = {int(r[0]): r for r in cards if len(r) >= 20}
    bycode = defaultdict(list)
    for r in cards:
        if len(r) >= 20:
            bycode[r[C_CODE]].append(int(r[0]))

    def sig(i):
        r = byid[i]
        return (norm_rules(r[C_TEXT]), norm_rules(r[C_TRAITS]),
                tuple(_nz(v) for v in r[C_STATS]))

    is_repack = lambda cid: int(byid[cid][C_PACK]) in REPACK_PACKS

    groups = defaultdict(list)
    for r in cards:
        if len(r) < 20:
            continue
        groups[(norm_name(r[C_NAME]), r[C_TYPE], r[C_SPHERE])].append(int(r[0]))

    merge = {}          # loser_id -> canonical_id
    new_repack = []     # repackaged cards with no original match (stay canonical)
    ambiguous = []      # repackaged card matching >1 original with no rules/override pick
    for key, ids in groups.items():
        originals = [i for i in ids if not is_repack(i)]
        reprints = [i for i in ids if is_repack(i)]

        # Step A: rules-equivalence among the ORIGINALS -> one canonical per ruleset.
        canon_by_sig = {}
        for i in sorted(originals):
            canon_by_sig.setdefault(sig(i), i)
        for i in originals:
            c = canon_by_sig[sig(i)]
            if i != c:
                merge[i] = c
        orig_canons = sorted(set(canon_by_sig.values()))

        # Step B: attach each repackaged reprint to its original.
        for rp in reprints:
            if not orig_canons:
                new_repack.append(rp)                 # genuinely new (e.g. RevCore campaign cards)
            elif len(orig_canons) == 1:
                merge[rp] = orig_canons[0]            # single original -> merge (ignore typos)
            elif sig(rp) in canon_by_sig:
                merge[rp] = canon_by_sig[sig(rp)]     # disambiguate by matching rules
            elif byid[rp][C_CODE] in REPACK_OVERRIDE_BY_CODE:
                merge[rp] = bycode[REPACK_OVERRIDE_BY_CODE[byid[rp][C_CODE]]][0]
            else:
                ambiguous.append((rp, orig_canons))

    # ---- curated force-merges (editorially-reworded alt-arts among originals) ----
    forced = 0
    for lo_code, ca_code in FORCE_MERGE_BY_CODE.items():
        los, cas = bycode.get(lo_code, []), bycode.get(ca_code, [])
        if not los or not cas:
            print("  NOTE: force-merge codes %s/%s not both present; skipping" % (lo_code, ca_code))
            continue
        merge[los[0]] = cas[0]
        forced += 1

    if ambiguous:
        print("  !! AMBIGUOUS repackaged reprints (need REPACK_OVERRIDE_BY_CODE): %d" % len(ambiguous))
        for rp, cands in ambiguous:
            print("     %r code %s [pack %s] -> one of %s" % (
                byid[rp][C_NAME], byid[rp][C_CODE], byid[rp][C_PACK], cands))

    # resolve any chains, drop self-merges
    def resolve(x):
        seen = set()
        while x in merge and x not in seen:
            seen.add(x); x = merge[x]
        return x
    for lo in list(merge):
        merge[lo] = resolve(merge[lo])
    merge = {l: c for l, c in merge.items() if l != c}

    # ---- invariants & tripwires ----
    assert all(c not in merge for c in merge.values()), "a canonical is itself a loser (chain)"
    for ca in set(merge.values()):
        assert ca in byid, "canonical %d missing" % ca
    for a_code, b_code in KEEP_SEPARATE_CODES:
        a, b = bycode.get(a_code, []), bycode.get(b_code, [])
        assert a and b, "keep-separate codes %s/%s not present" % (a_code, b_code)
        assert merge.get(a[0]) != b[0] and merge.get(b[0]) != a[0], \
            "regression: %s and %s must stay separate cards" % (a_code, b_code)
    for lo_code, ca_code in FORCE_MERGE_BY_CODE.items():
        los, cas = bycode.get(lo_code, []), bycode.get(ca_code, [])
        if los and cas:
            assert merge.get(los[0]) == cas[0], \
                "force-merge %s -> %s did not take" % (lo_code, ca_code)

    # names that REMAIN as multiple distinct cards after all merges (eyeball these)
    survivors = defaultdict(list)
    for cid in byid:
        if cid not in merge:
            r = byid[cid]
            survivors[(norm_name(r[C_NAME]), r[C_TYPE], r[C_SPHERE])].append(cid)
    kept_separate = sorted((byid[v[0]][C_NAME], sorted(v)) for v in survivors.values() if len(v) > 1)

    print("merge entries (losers): %d  ->  %d cards remain  (auto + %d forced)" % (
        len(merge), len(byid) - len(merge), forced))
    print("canonical cards gaining extra printings: %d" % len(set(merge.values())))
    if new_repack:
        print("repackaged cards with no original (kept as new canonical cards): %d" % len(new_repack))
    print("\nnames KEPT as multiple distinct cards (different rules; eyeball these): %d" % len(kept_separate))
    for name, canons in kept_separate[:40]:
        print("    %-32s -> %d cards (ids %s)" % (name[:32], len(canons), canons))
    if len(kept_separate) > 40:
        print("    ... and %d more" % (len(kept_separate) - 40))

    # ---- exposure of the destructive step ----
    losers = set(merge)
    for tbl in ('deckslot', 'decklistslot', 'decksideslot', 'decklistsideslot', 'review'):
        rows = load_table(tbl)
        if not rows:
            print("  %s: (no rows)" % tbl); continue
        idx = 1 if tbl == 'review' else 2   # review: (id, card_id, ...); slots: (id, container_id, card_id, ..)
        cnt = sum(1 for r in rows if len(r) > idx and r[idx].isdigit() and int(r[idx]) in losers)
        print("  %s: %d rows reference a losing card" % (tbl, cnt))

    loser_codes = {byid[l][C_CODE] for l in losers}
    dc = load_table('deckchange')
    if dc:
        hit = sum(1 for r in dc if len(r) > 3 and any(('"%s"' % c) in r[3] for c in loser_codes))
        print("  deckchange: %d/%d variations reference a losing card code (history view only)" % (hit, len(dc)))

    emit_sql(merge)
    print("\nwrote %s" % os.path.join(HERE, '02_migrate.sql'))


def emit_sql(merge):
    packs = ','.join(str(p) for p in sorted(REPACK_PACKS))
    canon_ids = ','.join(str(c) for c in sorted(set(merge.values()))) or '0'
    map_values = ',\n  '.join('(%d,%d)' % (lo, ca) for lo, ca in sorted(merge.items()))

    L = []
    w = L.append
    w("-- Data migration for the canonical-Card + CardPrinting refactor.")
    w("-- Rules-equivalence dedup: only true reprints/alt-arts (identical rules)")
    w("-- merge; genuinely-different same-named cards stay separate.")
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
    if map_values:
        w("INSERT INTO _merge_map (loser_id, canonical_id) VALUES")
        w("  " + map_values + ";")
    w("")
    w("-- 3) Repoint the losers' printings onto the canonical card. (Merged cards share")
    w("--    the same rules, so printings carry only art differences -- no rules overrides.)")
    w("UPDATE card_printing cp JOIN _merge_map m ON cp.card_id = m.loser_id SET cp.card_id = m.canonical_id;")
    w("")
    w("-- 4) Repoint every saved reference (FKs to card.id).")
    for tbl in ('deckslot', 'decklistslot', 'decksideslot', 'decklistsideslot', 'review'):
        w("UPDATE %s s JOIN _merge_map m ON s.card_id = m.loser_id SET s.card_id = m.canonical_id;" % tbl)
    w("")
    w("-- NOTE: deck-edit history (deckchange.variation) stores card CODES in JSON and is")
    w("--   intentionally NOT rewritten -- loser codes become unresolved in the history")
    w("--   view only (deck CONTENTS via *_slot are repointed above and stay correct).")
    w("")
    w("-- 5) Collapse duplicate printings now sharing the same canonical card AND pack")
    w("--    (e.g. a card printed twice in one pack): keep one, sum quantity.")
    w("UPDATE card_printing cp JOIN (")
    w("    SELECT MIN(id) keep_id, SUM(quantity) tq FROM card_printing")
    w("    GROUP BY card_id, pack_id HAVING COUNT(*) > 1")
    w("  ) d ON cp.id = d.keep_id SET cp.quantity = d.tq;")
    w("DELETE cp FROM card_printing cp JOIN (")
    w("    SELECT card_id, pack_id, MIN(id) keep_id FROM card_printing")
    w("    GROUP BY card_id, pack_id HAVING COUNT(*) > 1")
    w("  ) d ON cp.card_id = d.card_id AND cp.pack_id = d.pack_id AND cp.id <> d.keep_id;")
    w("")
    w("-- 6) Dedup any slot that now has two rows for the same canonical card (sum qty).")
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
    w("-- 7) Delete the now-merged loser card rows.")
    w("DELETE c FROM card c JOIN _merge_map m ON c.id = m.loser_id;")
    w("")
    w("-- 8) Flag the repackaged products (collection page groups them separately).")
    w("UPDATE pack SET is_repackaged = 1 WHERE id IN (%s);" % (packs or '0'))
    w("")
    w("DROP TEMPORARY TABLE _merge_map;")
    w("COMMIT;")
    w("")
    with open(os.path.join(HERE, '02_migrate.sql'), 'w') as f:
        f.write('\n'.join(L))


if __name__ == '__main__':
    main()
