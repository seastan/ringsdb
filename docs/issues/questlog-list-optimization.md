# Quest log lists: too many SQL queries

Status: fixed (`popular`: 3,218 → 8 queries, `find`: 3,220 → 9). The 4 queries left for the deck
snapshots go away with the rework of [questlog-deck-snapshots.md](questlog-deck-snapshots.md).
Proposals 4 and 5 of [decklist-list-optimization.md](decklist-list-optimization.md) (cost of the
COUNT, caching) apply here too and remain open.

## Problem

`GET /questlogs/{type}/{page}` (`ListQuestlogController`) shows 30 quest logs per page, each with 1
to 4 decks and their heroes. It ran about **3,200 SQL queries** on the test fixtures, from only 14
distinct SQL statements:

| Page | Queries | Distinct |
|---|---:|---:|
| `popular` | 3,218 | 14 |
| `recent` | 3,384 | 14 |
| `hottopics` | 3,328 | 14 |
| `halloffame` (8 quest logs) | 953 | 14 |
| `find?sort=popularity` (search) | 3,220 | 16 |

`favorites` and `mine` run the same code on the same kind of Paginator, so they had the same
problem.

Two causes add up:
- the N+1 of the [fellowship lists](fellowship-list-optimization.md): every quest log lazy-loads
  its decks, then every decklist, then every slot and card of every decklist;
- above all, the **deck snapshots** ([questlog-deck-snapshots.md](questlog-deck-snapshots.md)):
  before rendering, `SnapshotManager::setSnapshots()` rewrites the private deck of every quest log
  deck with the JSON snapshot of the quest log, looking up every card of the snapshot one by one.

### Compared with production

The fixtures hold 50 quest logs of 1 to 4 decks (`VolumeFixtures`), with the contents of production
decklists. In production, quest logs have up to 4 decks of 50 cards and the oldest snapshots use the
codes from before the card-printings merge, so the figures are of the same order: about 100 queries
per deck shown.

## Measuring

`tests/Controller/QuestlogListQueriesTest.php` requests `popular`, `recent`, `hottopics`,
`halloffame` and `find?sort=popularity` with the profiler on and asserts the exact number of
queries (same model as `FellowshipListQueriesTest`). `DUMP_QUERIES=1` writes every query, with its
origin, to `var/log/queries/questlogs-*.txt`.

To get a realistic page, `VolumeFixtures` now creates 50 public quest logs, with every kind of
quest log deck, by deck number:
- a published decklist, from a private deck still there;
- a published decklist, from a deleted deck;
- an unpublished private deck;
- a deleted deck, unpublished (the list shows `[deleted]`).

Every other quest log stores its snapshots with old codes (`card_printing.image_code`), which go
through the fallback of `CardManager::findCardByCode()`. The private decks have no slots: the tests
that compute statistics on every deck (`SuggestionsCommandTest`) only see the fixture decks, and the
query count is the same.

The fixtures are now loaded with `--no-debug` (`make fixtures`, `make test-fixtures`): in debug, the
DBAL middleware keeps every `INSERT` with its backtrace, and the load ran out of memory.

## Inventory before the fix (`popular`, test fixtures, 3,218 queries)

| Count | Query | Origin |
|---:|---|---|
| 2,390 | `card WHERE code = ? LIMIT 1` | `CardManager::findCardByCode()`, from `SnapshotManager::applySnapshot()` |
| 486 | `card_printing WHERE image_code = ? LIMIT 1` | the fallback of `findCardByCode()`, for old codes |
| 86 | `card WHERE id = ?` | the card of the printing found by the fallback, `Card::getDeckLimit()` in `applySnapshot()` |
| 44 | `deck WHERE id = ?` | the private deck rewritten by `applySnapshot()` |
| 44 | `deckslot WHERE deck_id = ?` | its slots, removed by `applySnapshot()` |
| 44 | `decksideslot WHERE deck_id = ?` | its side slots, removed by `applySnapshot()` |
| 33 | `decklist WHERE id = ?` | `deck.decklist`, `public-questlogs.html.twig:113` |
| 33 | `decklistslot WHERE decklist_id = ?` | `decklist.getSlots().getHeroDeck()`, `public-questlogs.html.twig:114` |
| 30 | `questlog_deck WHERE questlog_id = ?` | `Questlog::getDecks()` in `SnapshotManager::setSnapshot()` |
| 10 | `user WHERE id = ?` | `questlog.user`, `public-questlogs.html.twig:93` |
| 8 | `type WHERE id = ?` | `card.type` in `getHeroDeck()` |
| 7 | `sphere WHERE id = ?` | `card.sphere.code`, `public-questlogs.html.twig:116` |
| 2 | the page of quest logs (`LIMIT 30`) | the Paginator, iterated twice (snapshots, then template) |
| 1 | Paginator `COUNT(*)` | `QuestLogManager::getPaginator()` |

About 3,050 queries come from the snapshots. Every card of every snapshot is looked up again, even
when it was already found for another deck of the page: `findOneBy()` runs a query, the identity
map only serves `find()` by id.

Part of this work was useless: the template displays the **decklist** of a quest log deck when it
has one and ignores its deck, which `applySnapshot()` had just rewritten. On the fixtures, half of
the quest log decks have a decklist.

On `find`, the search form added `SELECT id FROM pack` (`ListQuestlogController::searchForm()`,
used only when no pack is selected) and the cycles with their packs (already one query with
`CycleRepository::findAllWithPacks()`). With `cards[]` in the query string,
`QuestLogManager::findQuestLogsWithComplexSearch()` also loaded each requested card with its own
`findOneBy()`.

## Fix

The rendered pages are unchanged and the snapshots still work the same way (see
[questlog-deck-snapshots.md](questlog-deck-snapshots.md) for their rework); only the way they are
loaded changes.

1. **Fetch-join the author** in `QuestLogManager::getQueryBuilder()` (`JOIN d.user u`, selected),
   as for fellowships. The search reuses `u` for the author filter and the reputation sort, and the
   favorites join is now `f`.
2. **Materialize the Paginator** in the controller (`iterator_to_array()`): the Paginator runs its
   query on every iteration, and the page was iterated twice.
3. **Preload the quest log decks**: `QuestlogRepository::loadDecks()` loads the decks of the page
   with their decklist and private deck in one fetch join, ordered by `qd.id` (the order of the
   former lazy load).
4. **Preload the decklist slots**: `DecklistRepository::loadSlots()` (decklist lists, home page,
   fellowships) loads the slots, cards, types and spheres of the decklists in one query.
5. **Snapshots** (`SnapshotManager::setSnapshots()`):
   - the quest log decks with a decklist are skipped: the lists do not display their deck. This
     also stops rewriting live private decks for nothing (see the correctness issue in
     [questlog-deck-snapshots.md](questlog-deck-snapshots.md));
   - the cards of all the snapshots of the page are looked up together:
     `CardManager::findCardsByCodes()` loads the cards by code, then the printings of the codes not
     found by `image_code` (same fallback as `findCardByCode()`), with their types and spheres, in
     2 queries at most;
   - the slots and side slots of the private decks are loaded together:
     `DeckRepository::loadSlots()`, one query each (joining both would return their product).
   `applySnapshot()`, also used by the quest log export (`QuestlogArchiver`), uses
   `findCardsByCodes()` too: 2 queries per deck instead of one or two per card. The unused
   `setSnapshot()` is removed.
6. **Search**: the `SELECT id FROM pack` is dropped (with no pack selected, every pack is checked
   anyway), and the requested cards are loaded with one `findBy(['code' => …])`.

## Result

Whatever the data volume:
- **8 queries** for `popular`, `recent`, `hottopics` and `halloffame` (`favorites` and `mine` run
  the same code, but are not measured: they need a logged-in user): the
  COUNT, the page with its authors, the quest log decks with their decklists and decks, the
  decklist slots with their cards, types and spheres, then for the snapshots the cards, the
  printings of the old codes, the deck slots and the deck side slots;
- **9 queries** for `find`: the same 8, plus the cycles with their packs; plus 2 with `cards[]` in
  the query string (the requested cards for the search, and their rows for the form).

The snapshot queries only run when needed: no printings query when every code is a card code, no
deck slots query when no quest log deck of the page has a private deck without a decklist.

The rendered pages are unchanged: the page snapshots of `WebsiteBrowsingTest` for `popular` and
`recent` were regenerated with the former code on the new fixtures, and the new code passes them.

## Not done

- `MyListQuestlogController` (`/myquestlogs`) benefits from the batched snapshots (cards and deck
  slots), but still lazy-loads the decks of each quest log and the slots of each decklist: the same
  preloads (steps 3 and 4) would apply there.
- Storing the snapshots as slots ([questlog-deck-snapshots.md](questlog-deck-snapshots.md)) would
  replace the 4 snapshot queries with one preload of the quest log deck slots, and remove the
  rewriting of the live private decks.
