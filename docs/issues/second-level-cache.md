# Second-level cache: what it will and will not cover

Status: analysis. Follows proposal 5 of [index-optimization.md](index-optimization.md).

## Context

The reference entities are already annotated with
`#[ORM\Cache(usage: 'NONSTRICT_READ_WRITE', region: 'entity_region')]`: `Card`, `CardPrinting`,
`Pack`, `Cycle`, `Sphere`, `Type`, `Scenario`, `Encounter`. The cache is not enabled in
`config/packages/doctrine.yaml`.

Once enabled, the second-level cache only serves:
- `find($id)`;
- the initialization of a `ManyToOne` proxy (`$printing->getPack()`, `$slot->getCard()`);
- the collections whose **association** carries `#[ORM\Cache]`.

Every other read still hits the database. The sections below list them.

## 1. DBAL reads (never cached)

| Code | Reference tables read |
|---|---|
| `Decklist/SearchDecklistController`, `Fellowship/SearchFellowshipController`, `Questlog/SearchQuestlogController` | `sphere`, `pack` |
| `Decklist/ListDecklistController:110,154,157,165`, `Fellowship/ListFellowshipController:102,148`, `Questlog/ListQuestlogController:105,151` | `pack`, `sphere`; `card` + `sphere` + `type` + `card_printing` + `pack` for the card filters |
| `CardSearch/SearchFormController:58` | `card_printing` (list of illustrators) |
| `Services/DecklistManager:395` | `SELECT DISTINCT pack_id FROM card_printing` |
| `Admin/Stat/*`, `Stats/CardStatsCalculator`, `Command/PrecomputeCardStatsCommand`, `Command/SuggestionsCommand` | analytical queries; not a target for the cache |

The other DBAL calls (favorites, votes, `API/UserInfoController`, patrons) do not read cached
tables.

## 2. ORM queries (DQL, QueryBuilder, `findBy` / `findOneBy`)

A query on a cached entity still runs; the cache only **stores** the hydrated entities. The query
itself is cached only with `setCacheable(true)` and the query cache region. No query calls
`setCacheable()` today.

- **Card lookups by code**, all over the application: `Services/CardManager:27,32`,
  `Services/DecklistManager:363`, `Services/QuestLogManager:286`, `Services/FellowshipManager:305`,
  `Services/CustomPackManager:32`. The entity cache is keyed by id, not by code.
- `Services/CardsData`: `findBy` on cycles and spheres (`:72`, `:123`), `get_search_rows`
  (QueryBuilder), `getDistinctTraits`. The latter returns scalars, and the query cache only handles
  entity results, so it cannot be cached that way.
- `Services/DeckImporter:56-68`, `Services/DecklistFactory:55`, `Services/DecklistManager:283`.
- Fetch-join queries: the joined entities are hydrated from the result set anyway.

## 3. Collections without `#[ORM\Cache]` (the main gap)

The annotation sits on the classes only. Every collection initialization is still a query:

- **`Card::printings`**: the hottest path. `CardsData::getCardInfo` (`:480`, `:494`) calls
  `getPrimaryPrinting()` and `getPrintings()`, so one query per card. The `getPack()` calls that
  follow are served by the cache.
- `Cycle::packs`: loops in `Collection/GetPacksController` and in the List/Search controllers.
- `Pack::printings`: `CardsData:83`.
- `Scenario::encounters`.

Adding `#[ORM\Cache]` to these associations is straightforward, since their targets are cacheable.
Do not add it to `Card::reviews` or `Scenario::questlogs`: `Review` and `Questlog` are not
cacheable, and Doctrine throws.

## 4. Invalidation

- **`Admin/Card/ForceDeleteCardController:40`** deletes `card_printing` rows with DBAL before the
  ORM `remove()`. The `CardPrinting` entries stay in the entity region, and a cached
  `Pack::printings` collection would go stale. Let the ORM delete them (`cascade: remove` already
  exists on `Card::printings`) or evict them explicitly.
- **Console commands** (`ScrapBeorn*`, `FixCanonicalNames`) write through the ORM, so they evict
  the cache, but in the CLI process. **With an APCu pool, the CLI and PHP-FPM caches are separate**:
  CLI evictions never reach the web. Use a shared pool (filesystem or Redis), or clear the regions
  after these commands. The `Admin/Command` form runs in the web process and is not affected.
- **SQL outside the application** (SQL migrations, dump restores, Adminer, `make fixtures`): clear
  the regions afterwards (`doctrine:orm:clear-cache:region:*`), and add it to `deploy.sh`.
- **Tests**: the test database is reloaded with SQL. Use an `array` pool or disable the cache in the
  `test` environment, otherwise entries from a previous test can leak.
- The Excel/CSV imports and the admin CRUD go through the ORM: nothing to do.
  `NONSTRICT_READ_WRITE` is acceptable, since writes are rare and admin-only.

## Recommendations

Enabled as is, the cache only speeds up `ManyToOne` proxies (`Deckslot → Card → Type/Sphere`,
`CardPrinting → Pack → Cycle`). To get the full benefit:
1. cache the `Card::printings` and `Cycle::packs` collections (and `Pack::printings`,
   `Scenario::encounters`);
2. make the card-by-code lookups and the small lists (cycles, spheres, packs) cacheable queries;
3. replace the DBAL `SELECT`s on `sphere` / `pack` with these cached ORM queries;
4. pick a pool shared by the CLI and the web, and clear the regions on deploy;
5. fix `ForceDeleteCardController`.
