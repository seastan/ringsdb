# Home page: too many SQL queries

Status: open. Proposal 1 is implemented (475 → 185 queries); the inventory below is the state before it.

## Problem

`GET /` (`IndexController`) runs **475 SQL queries** on the test fixtures (production-like volume,
see [Fixtures](#fixtures)). Only 22 distinct SQL statements are behind them; the rest are N+1:
- 266 load a single card (`SELECT … FROM card t0 WHERE t0.id = ?`);
- 150 load the comments of one decklist, fellowship or review.

With the original fixtures only (4 decklists, 1 fellowship, 1 review, 1 comment), the page ran 122
queries. These fixtures did not show the fellowship block or the comment N+1.

### None of these queries is raw DBAL

No query on this page is hand-written DBAL. The `t0.*` queries that look hand-written are ORM
**lazy loads**, run by the entity and collection persisters when a template or the controller reads
an association that is not loaded yet. The raw DBAL queries in `src/` (`executeQuery`, `fetch*`)
belong to other pages: decklist, fellowship and quest log lists and searches, admin, stats, and the
API user info.

So on this page, the fix is to load the associations up front, not to rewrite DBAL code.

## Measuring

`tests/Controller/IndexQueriesTest.php` requests `/` with the profiler on. It reads the queries from
the `DoctrineDataCollector` (`tests/Controller/DatabaseQueriesTrait.php`) and asserts their exact
number. When the count changes, the test fails and lists the queries grouped by SQL, each with its
origin. Then update `EXPECTED_QUERIES`.

Each query's origin is the first frame in `src/` plus the template line. It comes from
`profiling_collect_backtrace` (`config/packages/test/doctrine.yaml`); compiled Twig frames are mapped
back to the template line.

```bash
make test-fixtures   # loads VolumeFixtures too
docker compose exec -T -u www-data -e XDEBUG_MODE=off -e DUMP_QUERIES=1 symfony \
    php vendor/bin/phpunit tests/Controller/IndexQueriesTest.php
# full list, with parameters and origins: var/log/queries/index.txt
```

The trait works for any page: `$client->enableProfiler()`, then `databaseQueries($client)`.

### Fixtures

`src/DataFixtures/VolumeFixtures.php` gives the test and dev databases a production-like volume, so
that every block of the page is filled and every N+1 is reached:
- 10 users;
- 60 decklists with the cards of 60 different production decklists (all with different heroes),
  with a lorem ipsum name, description and 3 comments each (the last comment of every 10th
  decklist is hidden);
- 50 public fellowships of 4 of those decklists, with 2 comments each;
- 50 card reviews, with 2 comments each.

They are dated 2014, before the other fixtures, which stay first in the lists sorted by date. The
review cards are outside the Core Set that the tests use.

The decklist contents come from `src/DataFixtures/volume-decklists.json`. It holds the cards only
(`slots`, `sideslots`), the last pack, and the source URL, taken from the public API
(`https://ringsdb.com/api/public/decklist/{id}` and `/api/public/decklists/by_date/{date}`). It
holds no name, description or user. Every card code exists in `ringsdb_bootstrap.sql`; the fixture
fails on an unknown code.

## Inventory (test fixtures, 475 queries)

| Count | Query | Origin |
|---:|---|---|
| 266 | `card WHERE id = ?` | `SlotCollectionDecorator::getHeroDeck()` → `Card::getType()`, from `Default/index.html.twig:30, 108` |
| 50 | `comment WHERE decklist_id = ?` | `$decklist->getComments()->last()`, `IndexController.php:168` |
| 50 | `fellowshipcomment WHERE fellowship_id = ?` | `$fellowship->getComments()->last()`, `IndexController.php:193` |
| 50 | `reviewcomment WHERE review_id = ?` | `$review->getComments()->last()`, `IndexController.php:232` |
| 14 | `decklistslot WHERE decklist_id = ?` | `getHeroDeck()`, `index.html.twig:30, 108, 155, 232` |
| 8 | `user WHERE id = ?` | `decklist.user`, `fellowship.user`, `comment.user`: `index.html.twig:47, 94, 172, 218`, `macros.html.twig:66` |
| 13 | `pack WHERE id = ?` | `decklist.lastPack.name`, `index.html.twig:42, 119, 167, 243` |
| 6 | `sphere WHERE id = ?` | `card.sphere.code`, `index.html.twig:32` |
| 4 | `decklist WHERE id = ?` | `deck.decklist` in the fellowship block, `index.html.twig:107` |
| 2 | `fellowship_decklist WHERE fellowship_id = ?` | `fellowship.decklists`, `index.html.twig:104, 228` |
| 1 | `type` (findAll) | `IndexController.php:40` |
| 1 | `scenario` (findBy, daily challenge) | `IndexController.php:50` |
| 4 | trending and new decklists and fellowships (LIMIT 3/1/6/2) | `IndexController.php:68, 83, 99, 129` |
| 2 | Paginator `COUNT(*)` on the decklists and fellowships "by recent discussion" | `DecklistManager.php:143`, `FellowshipManager.php:128` |
| 2 | decklists and fellowships by recent discussion (LIMIT 50) | `IndexController.php:164, 189` |
| 2 | recent reviews and recent review discussion (LIMIT 50) | `IndexController.php:211, 228` |

There are **12 fixed queries**. Everything else (463) is N+1.

### N+1 in the template: hero cards

`decklist.getSlots().getHeroDeck()` (`src/Model/SlotCollectionDecorator.php:176`) loads the slots,
then loops over **every** slot and calls `$slot->getCard()->getType()` to keep the heroes. Every card
of the deck is loaded one by one, only to display 1–3 heroes. With native lazy objects, reading
`getType()` initializes the card. The type itself costs nothing, because the unused
`TypeRepository::findAll()` (`$typeNames`, `IndexController.php:39`) has already put every Type in
the identity map.

This applies to every decklist shown (trending and new, up to 6) and to every deck of the fellowships
shown (up to 2 × 4): 14 slot collections here. Each fellowship also adds:
- `fellowship.decklists`: 1 query;
- `deck.decklist`: 1 query per deck;
- `fellowship.user`: 1 query.

Then come `card.sphere` (1 per distinct sphere, at most 7), `decklist.lastPack` and
`decklist.user` (1 per distinct entity).

### N+1 in the controller: recent comments

The "recent comments" block fetches 50 decklists, 50 fellowships, 50 reviews and 50 reviews by last
comment. For each one, `getComments()->last()` loads the **whole** comment collection
(`IndexController.php:162, 186, 236`). It then keeps 8 comments. The "BUG" comment in the controller
explains the 50: `dateLastComment` cannot be trusted, so it reads a lot to be safe.

`Review::$comments` has no `OrderBy`, so `last()` returns whatever row MySQL returns last. That is not
reliably the latest comment.

The template then loads `comment.user` (up to 8 queries) and `comment.review.card`.

### Compared with production

The fixtures reach every N+1 of the page, with 14 different displayed decks (266 distinct cards).
The count in production should be of the same order, about 450–500 queries. Real comment threads
are longer, but each one is still a single query. A dump on a prod copy would confirm the figure.

### Caching that does not apply

- **HTTP cache.** The controller sets `public, max-age=CACHE_EXPIRATION`, but the dev server answers
  `Cache-Control: max-age=0, must-revalidate, private`. The layout reads
  `app.session.flashbag.get(...)` (`templates/layout.html.twig:173-181`). That starts the session,
  so Symfony's `AbstractSessionListener` turns the response private.
- **Second-level cache.** `#[ORM\Cache]` is declared on Card, CardPrinting, Cycle, Encounter, Pack,
  Scenario, Sphere and Type, but `second_level_cache` is not enabled, so it has no effect.
- **Result cache.** The Doctrine result cache is configured in prod
  (`config/packages/prod/doctrine.yaml`), but no query uses it.

## Proposals

They are ordered by gain over effort. Figures are queries saved on the fixtures (475 queries).

### 1. Load the displayed decks in two queries (−292) — done

Implemented in `IndexController::loadDisplayedDecks()`: 475 → 185 queries (−292 lazy loads, +2
fetch-join queries). The joins are `LEFT JOIN`s, so a fellowship without decklists or a decklist
without slots is still marked as loaded, and the fellowship decklists are ordered by id.

After the 4 decklist and fellowship queries, load the decklists of the displayed fellowships with a
fetch join (`fellowship.decklists`, then `deck.decklist`: −6):

```php
$em->createQuery('SELECT f, fd, d FROM App\Entity\Fellowship f JOIN f.decklists fd JOIN fd.decklist d WHERE f.id IN (:ids)')
```

Then collect the ids of every decklist that will be displayed: the trending and new decklists plus
the decklists of those fellowships. Initialize their slots with one more fetch join (266 cards,
14 slot collections and 6 spheres: −286):

```php
$em->createQuery('SELECT d, s, c FROM App\Entity\Decklist d JOIN d.slots s JOIN s.card c WHERE d.id IN (:ids)')
    ->setParameter('ids', $ids)
    ->getResult();
```

The decklists are already managed, so Doctrine fills their `slots` collections and the cards in
the identity map. `getHeroDeck()` then runs without a query.
- Spheres: add `JOIN c.sphere sp` to the select, or load all 7 spheres up front like the types.
- This loads the full decks (about 35 cards each) where only the heroes are needed, but in one
  query instead of hundreds.

Do **not** filter the join on `type.code = 'hero'`. Doctrine would mark a partial `slots`
collection as initialized, and every later reader of `getSlots()` in the same request would get
only the heroes.

Alternatives:
- Store the heroes on `decklist` (a denormalized column or table, written when the decklist is
  published). This is the cheapest at read time, but it adds data to keep consistent.
- The second-level cache on `Card` (proposal 5). The queries go away, but you still get hundreds of
  cache hits per page.

### 2. Recent comments: query the comments, not their parents (−150, plus some user loads)

Replace the 4 "LIMIT 50 + `getComments()->last()`" blocks with 4 queries on the comment tables.
Each query joins the user and the parent and returns the 8 most recent rows:

```sql
SELECT c, u, d FROM Comment c JOIN c.user u JOIN c.decklist d
WHERE c.isHidden = false ORDER BY c.dateCreation DESC, c.id DESC   -- LIMIT 8
```

Do the same for fellowship comments (on public fellowships), reviews (`JOIN r.user`, `JOIN r.card`,
with the `dateRelease IS NOT NULL` filter) and review comments. Then merge the 4 lists in PHP, sort,
and keep 8.

- This removes the 50-row workaround and its "BUG" comment, because `dateLastComment` is no longer
  used.
- **Behavior change:** today the page shows at most one comment per thread (the last one).
  Afterwards, a busy thread can fill several of the 8 slots. If that is not acceptable, keep one row
  per thread:
  - in native SQL with `ROW_NUMBER() OVER (PARTITION BY decklist_id ORDER BY date_creation DESC)`
    (MySQL 8), hydrated through a `ResultSetMapping`;
  - or in DQL with `c.id IN (SELECT MAX(c2.id) … GROUP BY c2.decklist)`.
- Today, a decklist whose last comment is hidden is skipped entirely. With the new query, the latest
  visible comment of that decklist is shown instead.
- Index needed: `comment(date_creation)`. The table has no index on it today, only `user_id` and
  `decklist_id`. Check the same on `fellowshipcomment`, `reviewcomment` and `review`.
- Add `#[ORM\OrderBy(['dateCreation' => 'ASC'])]` to `Review::$comments`. It fixes `last()`
  elsewhere too.

### 3. Fetch-join the ManyToOne associations in the main queries (about −15)

- Decklists (trending, new): add `JOIN d.user u LEFT JOIN d.lastPack p` and select them.
- Fellowships: add `JOIN f.user u`.
- To-one joins do not multiply rows, so they are safe with `LIMIT`.

### 4. Remove the useless Paginators and COUNTs (−2)

- `DecklistManager::getPaginator()` / `FellowshipManager::getPaginator()` run a `COUNT(*)` over the
  whole filtered table to fill `maxcount`, which the home page never reads. They also wrap a query
  that uses no fetch join.
- After proposal 2, the home page no longer calls these methods.
- The 4 Paginators in `IndexController` (`fetchJoinCollection = false`) should be
  `getQuery()->getResult()`.
- Also remove `TypeRepository::findAll()` once proposal 1 is in place. It only warms the identity
  map, and `$typeNames` is never used. Otherwise keep it, or `getType()->getCode()` would cost a
  query per type.

### 5. Enable the second-level cache for reference data (all pages)

The entities are already annotated. Enable it with:
- `doctrine.orm.second_level_cache` (`enabled: true`);
- a region `entity_region`, backed by a pool (APCu or filesystem in prod).

Cards, packs, spheres and types change only through the admin, which goes through the ORM, so
Doctrine invalidates them. This is a global gain, not specific to the home page. Card loads then
become cache hits, which is a good complement to proposal 1. Check first:
- whether the admin or the CSV/Excel imports write these tables with DBAL. If they do, they must
  clear the cache;
- whether `NONSTRICT_READ_WRITE` is acceptable.

### 6. Restore the HTTP cache (whole page, no PHP for anonymous visitors)

- Only read the flashes when a session exists, for example
  `{% if app.request.hasPreviousSession %}`, or use `app.flashes` behind the same guard. The layout
  then no longer starts a session for anonymous visitors, and the `public, max-age` set by the
  controller is kept.
- Check that nothing else in the layout starts the session. Then check what sits in front of PHP in
  prod (reverse proxy, CDN): without a shared cache, `public` only helps browsers.
- This change concerns every public page that sets `setPublic()`, not just the home page.
- Check with the dump: a page whose Cache-Control changes is a page that was starting a session for
  nothing.

### 7. Application cache of the home blocks

The home page is the same for every visitor; only the daily challenge changes, once a day. Cache
the computed data in a Symfony cache pool for a few minutes:
- the trending, new and comment ids;
- or the rendered HTML fragment of the blocks.

On a cache hit, the page costs 0–1 query. This sits on top of proposals 1–4 and does not replace
them: a cache miss has to stay cheap.

### 8. Cost per query (to check on a prod copy with `EXPLAIN`)

The query count is not everything:
- **Trending.** `ORDER BY (1+nbVotes)/(1+POWER(DATE_DIFF(NOW(), dateCreation), 2))` computes the
  score on every row with a description, then sorts. No index can help. The score drops with the
  square of age (a 30-day-old deck needs about 900 votes to beat a new deck with 0 votes), so a
  window `dateCreation > NOW() - INTERVAL N DAY` on the indexed `idx_decklist_date_creation` would
  barely change the result.
- **Recent discussion.** It sorts `decklist` by `date_last_comment` with no index on that column.
  Proposal 2 removes it.
- `LENGTH(description_html) > 0` cannot use an index. It is acceptable inside a date window.

## Target

With proposals 1–4, about **10–15 queries** whatever the data volume:
- 1 scenario;
- 4 decklists and fellowships with their users and packs;
- 1 for the fellowship decklists;
- 1 for slots, cards and spheres;
- 4 for comments, with users and parents.

With 6 or 7 on top, close to **0** for most visitors.

`IndexQueriesTest` then fails with the new count: update `EXPECTED_QUERIES` at each step. The
fixtures reach every N+1 of the page, so the count measures the real gain.
