# Fellowship lists: too many SQL queries

Status: fixed (`popular`: 802 → 4 queries, `find`: 774 → 5). Proposals 4 and 5 of
[decklist-list-optimization.md](decklist-list-optimization.md) (cost of the COUNT, caching) apply
here too and remain open.

## Problem

`GET /fellowships/{type}/{page}` (`ListFellowshipController`) shows 30 fellowships per page, each
with 2 to 4 decklists and their heroes. It ran about **800 SQL queries** on the test fixtures, from
only 10 distinct SQL statements:

| Page | Queries | Distinct |
|---|---:|---:|
| `popular` | 802 | 10 |
| `recent` | 803 | 10 |
| `hottopics` | 802 | 10 |
| `find?sort=popularity` (search) | 774 | 11 |

`favorites`, `mine` and `halloffame` run the same template on the same kind of Paginator, so they
had the same N+1 (`halloffame` is empty on the fixtures: no fellowship has more than 10 votes).

It is the decklist list problem, one level deeper: every fellowship lazy-loads its
`FellowshipDecklist` rows, then every decklist, then every slot and card of every decklist.

### Compared with production

On the fixtures, the 60 decklists of a page share their cards (595 distinct cards). In production,
the 30 fellowships of a page hold 60 to 120 decklists of 25 to 35 distinct cards each, from many
packs: expect **well over 1,000 queries per page**. A dump on a prod copy would confirm the figure.

## Measuring

`tests/Controller/FellowshipListQueriesTest.php` requests `popular`, `recent`, `hottopics` and
`find?sort=popularity` with the profiler on and asserts the exact number of queries (same model as
`IndexQueriesTest`). `DUMP_QUERIES=1` writes every query, with its origin, to
`var/log/queries/fellowships-*.txt`.

## Inventory before the fix (`popular`, test fixtures, 802 queries)

| Count | Query | Origin |
|---:|---|---|
| 595 | `card WHERE id = ?` | `SlotCollectionDecorator::getHeroDeck()` → `Card::getType()`, `public-fellowships.html.twig:100` |
| 60 | `decklist WHERE id = ?` | `deck.decklist`, `public-fellowships.html.twig:99` |
| 60 | `decklistslot WHERE decklist_id = ?` | `decklist.getSlots().getHeroDeck()`, `public-fellowships.html.twig:100` |
| 30 | `fellowship_decklist WHERE fellowship_id = ?` | `fellowship.decklists`, `public-fellowships.html.twig:96` |
| 30 | `pack WHERE id = ?` | `decklist.lastPack.name`, `public-fellowships.html.twig:111` |
| 10 | `user WHERE id = ?` | `fellowship.user`, `public-fellowships.html.twig:86` |
| 8 | `type WHERE id = ?` | `card.type` in `getHeroDeck()` |
| 7 | `sphere WHERE id = ?` | `card.sphere.code`, `public-fellowships.html.twig:102` |
| 1 | Paginator `COUNT(*)` | `FellowshipManager::getPaginator()` |
| 1 | the page of fellowships (`LIMIT 30`) | `public-fellowships.html.twig:64` |

Only the last 2 are fixed queries; the other 800 are ORM lazy loads run by the template.

On `find`, the search form added `SELECT id FROM pack` (`ListFellowshipController::searchForm()`,
used only when no pack is selected) and the cycles with their packs (already one query with
`CycleRepository::findAllWithPacks()`). `lastPack` cost nothing there, as the packs were already in
the identity map. With `cards[]` in the query string,
`FellowshipManager::findFellowshipsWithComplexSearch()` also loaded each requested card with its own
`findOneBy()`.

## Fix

1. **Fetch-join the author** in `FellowshipManager::getQueryBuilder()` (`JOIN d.user u`, selected).
   A to-one join does not multiply rows, so it is safe with `LIMIT` and the Paginator. The search
   reuses `u` for the author filter and the reputation sort, and the favorites join is now `f`.
2. **Materialize the Paginator** in the controller (`iterator_to_array()`): the Paginator runs its
   query on every iteration, and the page is now iterated twice (preload, then template).
3. **Preload the decklists**: `FellowshipRepository::loadDecklists()` loads the
   `FellowshipDecklist` rows, their decklists and last packs of the page in one fetch join, and
   returns the decklists. The rows are ordered by `fd.id`, the order of the former lazy load.
4. **Preload the slots**: `DecklistRepository::loadSlots()`, already used by the decklist lists and
   the home page, loads the slots, cards, types and spheres of those decklists in one query.
   `getHeroDeck()` then runs without a query.
5. **Search**: the `SELECT id FROM pack` is dropped (with no pack selected, every pack is checked
   anyway), and the requested cards are loaded with one `findBy(['code' => …])`.

Both preloads are separate queries rather than joins of the main query: the main query uses
`LIMIT` and `DISTINCT` with a Paginator built with `fetchJoinCollection = false`, and one join from
fellowships down to slots would return thousands of rows per page.

## Result

Whatever the data volume:
- **4 queries** for `popular`, `recent`, `hottopics`, `halloffame`, `favorites` and `mine`: the
  COUNT, the page with its authors, the decklists with their last packs, and the slots with their
  cards, types and spheres;
- **5 queries** for `find`: the same 4, plus the cycles with their packs; plus 2 with `cards[]` in
  the query string (the requested cards for the search, and their rows for the form).

The rendered pages are unchanged: the page snapshots of `WebsiteBrowsingTest` still pass.
