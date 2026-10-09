# Decklist lists: too many SQL queries

Status: open. Proposals 1 to 3 are implemented (`popular`: 149 → 15 → 3 queries, `find`:
182 → 48 → 5); the inventory below is the state before them. Proposals 4 and 5 remain.

## Problem

`GET /decklists/{type}/{page}` (`ListDecklistController`) shows 30 decklists per page and runs
about **150 SQL queries** on the test fixtures. Only 8 distinct SQL statements are behind them; the
rest are N+1:

| Page | Queries | Distinct |
|---|---:|---:|
| `popular` | 149 | 8 |
| `recent` | 150 | 8 |
| `hottopics` | 149 | 8 |
| `halloffame` | 2 | 2 |
| `find?sort=popularity` (search) | 182 | 11 |

`favorites` and `mine` run the same code as `recent` (same template, same Paginator) for a logged-in
user, so they have the same N+1.

`halloffame` only shows 2 queries because no fixture decklist has more than 10 votes: the page is
empty. With data, it has the same N+1 as the other lists.

### Compared with production

The fixtures share many cards (Core Set), so the 30 decklists of a page only load 97 distinct cards.
The 30 most popular or most recent decklists in production use many more distinct cards (25 to 35
per deck, from many packs). Expect several hundred card loads, plus up to 30 users and 10 to 20
packs: **about 400 to 900 queries per page**. A dump on a prod copy would confirm the figure.

### None of these N+1 is raw DBAL

As on the home page (see [index-optimization.md](index-optimization.md)), the `t0.*` queries are ORM
**lazy loads**, run when the template reads an association that is not loaded yet. The only raw
DBAL queries of the page are the 2 fixed queries of the search form.

## Measuring

The figures above come from the dev server (`docker compose up -d`, test fixtures loaded with
`make fixtures`): request the page, read the `X-Debug-Token` response header, then read the `db`
collector of that profile (`var/cache/dev/profiler/`, or `/_profiler/{token}?panel=db`).

To follow the optimization, add a `tests/Controller/DecklistListQueriesTest.php` on the model of
`IndexQueriesTest`: request `/decklists/popular` and `/decklists/find?sort=popularity` with the
profiler on, read the queries with `DatabaseQueriesTrait`, and assert their exact number.

`VolumeFixtures` should also give more than 10 votes to a few decklists, so that `halloffame` is not
empty.

## Inventory (`popular`, test fixtures, 149 queries)

| Count | Query | Origin |
|---:|---|---|
| 97 | `card WHERE id = ?` | `SlotCollectionDecorator::getHeroDeck()` → `Card::getType()`, from `Decklist/decklists.html.twig:67, 89` |
| 30 | `decklistslot WHERE decklist_id = ?` | `decklist.getSlots().getHeroDeck()`, `decklists.html.twig:67, 89` |
| 10 | `user WHERE id = ?` | `decklist.user`, `decklists.html.twig:84-86` |
| 4 | `type WHERE id = ?` | `card.type` in `getHeroDeck()` |
| 4 | `sphere WHERE id = ?` | `card.sphere.code`, `decklists.html.twig:69, 91` |
| 2 | `pack WHERE id = ?` | `decklist.lastPack.name`, `decklists.html.twig:79` |
| 1 | Paginator `COUNT(*)` | `DecklistManager::getPaginator()`, `DecklistManager.php:143` |
| 1 | the page of decklists (`LIMIT 30`) | `decklists.html.twig:64` (the Paginator runs the query when iterated) |

There are **2 fixed queries**. Everything else (147) is N+1.

### N+1 in the template: hero cards

`decklist.getSlots().getHeroDeck()` (`src/Model/SlotCollectionDecorator.php:176`) loads the slots,
then loops over **every** slot and calls `$slot->getCard()->getType()` to keep the heroes. Every card
of the deck is loaded one by one, only to display 1 to 3 heroes. The template calls it twice per
decklist (images, then names); the second call costs nothing.

Unlike the home page, nothing loads the types up front, so each type costs one more query. Then come
`card.sphere` (1 per distinct sphere), `decklist.lastPack` and `decklist.user` (1 per distinct
entity).

### N+1 in the search form

On `find`, `searchForm()` builds the pack checkboxes:
- 32 `pack WHERE cycle_id = ?`: `$cycle->getPacks()` for each cycle
  (`ListDecklistController.php:120, 137`);
- 3 fixed queries: `SELECT id FROM pack` (`:110`, when no pack is selected), the cycles (`:117`)
  and the spheres (`:154`).

With `cards[]` in the query string, `DecklistManager::findDecklistsWithComplexSearch()` also loads
each requested card with its own `findOneBy()` (`DecklistManager.php:363`).

## Proposals

They are ordered by gain over effort. Figures are queries saved on the fixtures (`popular`, 149
queries).

### 1. Preload the slots of the displayed page (−134 here, several hundred in prod) — done

Implemented in `DecklistRepository::loadSlots()`, called by `ListDecklistController` on the
materialized page and by `IndexController::loadDisplayedDecks()`: `popular` 149 → 15 queries,
`find` 182 → 48 (−135 lazy loads, +1 fetch-join query). What remains is proposals 2 and 3.

After the Paginator, load the slots, cards, types and spheres of the 30 decklists with one fetch
join:

```php
$em->createQuery('SELECT d, s, c, t, sp FROM App\Entity\Decklist d LEFT JOIN d.slots s LEFT JOIN s.card c LEFT JOIN c.type t LEFT JOIN c.sphere sp WHERE d IN (:decklists)')
    ->setParameter('decklists', $decklists)
    ->getResult();
```

The decklists are already managed, so Doctrine fills their `slots` collections and puts the cards
in the identity map. `getHeroDeck()` then runs without a query.

- This is the pattern of `IndexController::loadDisplayedDecks()`
  (`src/Controller/IndexController.php:265`). Move it to `DecklistManager` (or
  `DecklistRepository`) so that both pages share it.
- It cannot be a fetch join in the main query: that query uses `LIMIT`, `DISTINCT` and a Paginator
  built with `fetchJoinCollection = false`.
- **Materialize the Paginator** (`iterator_to_array($paginator)`) in the controller and pass the
  array to the template. `Paginator::getIterator()` runs the query on every call, so iterating it
  once to preload and once in the template would run the page query twice.
- Do **not** filter the join on `type.code = 'hero'`. Doctrine would mark a partial `slots`
  collection as initialized (same warning as on the home page).

### 2. Fetch-join the ManyToOne associations in the main query (−12) — done

Implemented: 15 → 3 queries on `popular`. `getQueryBuilder()` always joins and selects `d.user u`
and `d.lastPack p`; the search reuses `u`, and the favorites join is now `f`.

In `DecklistManager::getQueryBuilder()` (`DecklistManager.php:117`), add
`JOIN d.user u LEFT JOIN d.lastPack p` and select `u` and `p`.

- To-one joins do not multiply rows, so they are safe with `LIMIT` and the Paginator.
- `findDecklistsWithComplexSearch()` then reuses the `u` alias instead of its conditional
  `innerJoin('d.user', 'u')` (`DecklistManager.php:310`).

### 3. Search form: load the cycles with their packs (−32, −1 per requested card) — done

Implemented with `CycleRepository::findAllWithPacks()`: `find` 48 → 5 queries. With no pack
selected, every pack is checked, so the `SELECT id FROM pack` is simply dropped. The same
cycle/pack N+1 is still in `SearchDecklistController`, `GetPacksController`, `ListFellowshipController`,
`ListQuestlogController` and `SearchQuestlogController`: they can use `findAllWithPacks()` too.

- Load the cycles and their packs in one query:
  `SELECT c, p FROM App\Entity\Cycle c LEFT JOIN c.packs p ORDER BY c.position, p.position`
  (`Cycle::$packs` is already ordered by position).
- Take the pack ids from that result instead of `SELECT id FROM pack`.
- In `findDecklistsWithComplexSearch()`, load the requested cards with one
  `findBy(['code' => $cards_code])` instead of one `findOneBy()` per code.

### 4. Cost of the COUNT (to check on a prod copy with `EXPLAIN`)

The query count is not everything:
- The Paginator `COUNT(*)` wraps the whole `SELECT DISTINCT …` query, with every column and the
  computed `popularity`, over every decklist, on every page. A `COUNT(DISTINCT d.id)` without
  `ORDER BY` (`Paginator::setUseOutputWalkers(false)`, or a separate count query) avoids computing
  and sorting the score.
- Check whether `DISTINCT` is still needed: every join of the search (`d.user`, `d.spheres` on one
  sphere, `d.slots` on one card, `d.favorites` on one user) matches at most one row per decklist.
- `popular` sorts on a score computed for every decklist; `hottopics` runs a correlated subquery on
  `comment` for every decklist. No index can help either one (same remark as the "Trending" block
  of the home page).

### 5. Caching (all pages)

See proposals 5 and 6 of [index-optimization.md](index-optimization.md):
- the second-level cache, already declared on `Card`, `Type`, `Sphere` and `Pack` but not enabled;
- the HTTP cache: the controller sets `public, max-age` on every type except `favorites` and
  `mine`, but the layout reads the flashbag, which starts a session and turns the response private.

## Target

With proposals 1 to 3, whatever the data volume:
- **3 queries** for `popular`, `recent`, `hottopics`, `halloffame`, `favorites` and `mine`: the
  COUNT, the page with its users and packs, and the slots with their cards, types and spheres;
- **about 6 queries** for `find`: the same 3, plus cycles with packs, spheres, and the requested
  cards if any.
