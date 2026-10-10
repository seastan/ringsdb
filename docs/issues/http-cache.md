# HTTP cache: which responses are public, and which should be

Status: analysis. Extends proposal 6 of [index-optimization.md](index-optimization.md) and
proposal 5 of [decklist-list-optimization.md](decklist-list-optimization.md) to the whole
application.

## Summary

- **No HTML page is publicly cached today.** About twenty controllers call
  `setPublic()` + `setMaxAge()`. The layout reads the flashbag on every page, so Symfony rewrites
  all of them to `private, max-age=0, must-revalidate`.
- The site was designed for public pages. Login state, votes, favorites and author buttons are
  loaded in JavaScript (`/api/private/user/info`). Dark mode comes from a cookie, and CSRF is
  stateless. The flashbag read is the main thing that breaks it.
- **Removing the flashbag issue alone is not safe.** Several pages declared public render
  per-user content in PHP, or sit behind a `ROLE_USER` access rule. Once the session no longer
  forces `private`, a shared cache would serve them to the wrong visitors (section 2.B).
- The public API (`/api/public`) really is public, correctly so. Its weak point is
  `Last-Modified`, which often ignores part of the data it covers.
- Two private API routes (`/api/private/decks`, `/api/private/decks_by_user/{username}`) rely on
  `Last-Modified` + 304 on a URL that is the same for every user. On a shared browser, user B can
  be shown user A's cached deck list.

## 1. How the headers are produced today

- **Max-age.** `$cacheExpiration` is `CACHE_EXPIRATION` (600 s in `.env`), bound in
  `config/services.yaml:16`. Controllers use `setPublic()` + `setMaxAge()`. Nothing uses
  `setSharedMaxAge()`, an ETag or `Vary`.
- **No shared cache in the application.** There is no Symfony `HttpCache`, no
  `framework.http_cache` and no ESI (`public/index.php`, `src/Kernel.php`,
  `config/packages/framework.yaml`). `public` therefore only helps browsers, plus whatever sits
  in front of PHP in prod (reverse proxy, CDN). What sits in front of prod is not documented here.
- **Why HTML pages come out private:**
  - `templates/layout.html.twig:173-181` runs `app.session.flashbag.get('notice'|'warning'|'error')`
    on every page.
  - `Session::getFlashBag()` → `getBag()` → `SessionBagProxy::getBag()` increments the session
    usage index, even for an anonymous visitor without a session cookie.
  - `AbstractSessionListener::onKernelResponse()` (`vendor/symfony/http-kernel/EventListener/`,
    l. 203-213) sees a used session and, unless the response has the
    `NO_AUTO_CACHE_CONTROL_HEADER` header (never set here), rewrites the response:
    `Expires: now`, `private`, `max-age=0`, `must-revalidate`. A `public` directive is discarded.
- **Observed on the dev stack** (anonymous `curl -D -`):

  | URL | Cache-Control |
  |---|---|
  | `/`, `/about`, `/decklists` | `max-age=0, must-revalidate, private` |
  | `/api/public/cards/`, `/api/public/card/01001`, `/api/public/packs/` | `max-age=600, public` + `Last-Modified` |

  The `/api/public` firewall is `stateless`, and its controllers never render the layout.
- **What already supports public pages:**
  - `app.user.js` loads the user through `/api/private/user/info`. Pages pass
    `app.user.params.decklist_id` / `fellowship_id` / `questlog_id` / `card_id` to get `is_liked`,
    `is_favorite`, `is_author` and `can_delete`.
  - Dark mode is read from the `dark_mode` cookie in JavaScript (`layout.html.twig:4-12`).
  - Form CSRF tokens are stateless (`config/packages/csrf.yaml`).
  - No template rendered by a `setPublic()` controller uses `is_granted`, `app.user`,
    `csrf_token` or `app.session`. The only Twig session reads are in the layout and in
    `Security/layout.html.twig` (private pages).

## 2. HTML pages

### A. Declared public, content identical for every visitor

These become public as soon as the layout stops using the session.

| Route | Controller |
|---|---|
| `/` | `IndexController.php:38-39` |
| `/about` | `AboutController.php:23-24` |
| `/api/` (API intro) | `ApiIntroController.php:24-25` |
| `/patrons` | `Decklist/PatronsController.php:24-25` |
| `/search` | `CardSearch/SearchFormController.php:35-36` (GET form) |
| `/card/{code}`, `/set/{pack}`, `/cycle/{cycle}`, `/find` | `ZoomController`, `ListCardsController`, `GetCycleController`, `SimpleSearchController` forward to `CardSearch/DisplaySearchController.php:33-34` |
| `/decklist/view/{id}/{name}` | `Decklist/ViewDecklistController.php:29-30` |
| `/decklist/export/text/{id}`, `/decklist/export/octgn/{id}` | `TextExportDecklistController.php:28-29`, `OctgnExportDecklistController.php:28-29` |
| `/reviews/{page}` | `Review/ListReviewsController.php:26-27` |
| `/decklists/…`, `/fellowships/…`, `/questlogs/…` (`popular`, `recent`, `halloffame`, `hottopics`) | `ListDecklistController.php:38-39`, `ListFellowshipController.php:37-38`, `ListQuestlogController.php:40-41` |

In the three List controllers, the `favorites` and `mine` branches correctly switch to
`setPrivate()`, for example `ListDecklistController.php:53,64`. The URL is shared, but the content
is the current user's list.

The review and comment forms on `/card/{code}` and `/reviews` carry no CSRF token. Their HTML is
static, and the per-user state comes from JavaScript.

### B. Declared public, but must not be

These are latent bugs: today the session hides them, and they appear once the flashbag is fixed.

| Route | Controller | Problem |
|---|---|---|
| `/decklists/search`, `/fellowships/search`, `/questlogs/search` | `SearchDecklistController.php:33-34`, `SearchFellowshipController.php:33-34`, `SearchQuestlogController.php:33-34` | The pack checkboxes are pre-checked from `$this->getUser()->getOwnedPacks()`. The form HTML differs per user. |
| `/decklists/find`, `/fellowships/find`, `/questlogs/find` | `ListDecklistController.php:49`, `ListFellowshipController.php:48`, `ListQuestlogController.php:51` | `setUser($this->getUser())`. With `custom_packs[]` in the query, the results are filtered on the current user's custom packs (`DecklistManager.php:301,423-424`, `FellowshipManager.php:300,340`, `QuestLogManager.php:281,319`). The same URL gives different results per user. |
| `/user/reviews/{user_id}/{page}` | `Review/ByAuthorController.php:27-28` | The content is the same for everyone, but `^/user/` requires `ROLE_USER`. A shared cache would serve a login-only page to anonymous visitors. |
| `/user/profile/{id}/{name}/{page}` | `UserProfile/PublicProfileController.php:26-32` | Same `^/user/` issue. Also, `setPublic()` / `setMaxAge()` are dead code: `$response` is not passed to `render()`. |
| `/deck/import` | `DeckBuilder/ImportDeckController.php:22-23` | The template is static, but `^/deck/` requires `ROLE_USER`. Same issue. |

The last three are an access-control question more than a cache one. Profiles and reviews by
author are public content, so opening their routes (`PUBLIC_ACCESS`) is probably what was meant.
`/deck/import` is only useful to a logged-in user, so it should simply be private.

### C. Not declared public, could be

- Redirects only: `/d/{username}`, `/f/{username}`, `/q/{username}` (`ByAuthor*Controller.php`)
  and `/process` (`CardSearch/ProcessSearchController.php`). Low value, and optional.

### D. User-specific, must stay private (and are)

- `/deck/view/{id}` (`DeckBuilder/ViewDeckController.php`), `/fellowship/view/...` and
  `/questlog/view/...`. Each computes `is_owner` (owner toolbar in the template) and checks
  access to unshared items.
  - Making them cacheable would mean moving the owner toolbar to JavaScript, and caching only
    shared items.
  - Note: `^/fellowship/view/` and `^/deck/view/` are `PUBLIC_ACCESS`, but there is no such rule
    for `^/questlog/view/`, so quest logs are login-only.
- Deck exports and compare: `TextDeckExportController`, `OctgnDeckExportController`,
  `CompareDecksController`, `Text|OctgnListExportController`.
- Fellowship and quest log exports: `downloadFromSelection($this->currentUser(), …)`.
- The user's own lists and forms: `/decks`, `/myfellowships`, the questlog `MyList`, every
  New/Edit/PublishForm controller, `/collection/*`, the profile edit page.
- Security pages: login (`csrf_token('authenticate')`), register, resetting, change password,
  check email (session read/write).

### Side note: GET routes that write

These are outside the cache topic, but a cache or a prefetcher hitting them would have side
effects:
- `/deck/new` (`NewDeckController`) creates a deck;
- `CloneDeckController` (GET) and `CopyDeckController` (no `methods`);
- `/review/remove/{id}` (`RemoveReviewController`, no `methods`);
- `/user/remind/{username}` (`SendConfirmationEmailController`) sends an email.

## 3. API

### `/api/public` (stateless firewall, no session)

| Endpoint | Controller | Headers | Assessment |
|---|---|---|---|
| `/card/{code}.json` | `GetCardController.php:39-47` | public, `Last-Modified` = `card.dateUpdate`, 304 before serializing | The date ignores printings, packs, type and sphere names, and image presence (`imagesrc`). |
| `/cards/` | `ListCardsController.php:47-78` | public, `Last-Modified` = max(card, printing) | The best date of the card endpoints. Still ignores pack and cycle changes. The 304 check comes **after** the full query (`:56`), so only serialization is saved. |
| `/cards/{pack}.{_format}` | `ListCardsByPackController.php:40-68` | public, `Last-Modified` = max(card) | No printings in the date. No `Last-Modified` when empty. 304 after the query. `:46-49` returns a **cacheable 200** "format not supported" for xml/xls/xlsx. |
| `/cards/search/{q}` | `SearchCardsController.php:37-58` | same as ByPack | Same gaps. |
| `/packs/` | `ListPacksController.php:38-55` | public, `Last-Modified` = max(pack) | `known` (card count) and `cycle_position` are not covered by the date. |
| `/scenario/{id}.json` | `GetScenarioController.php:37-47` | public, `Last-Modified` = scenario | Encounters and pack name are not covered. Minor. |
| `/decklist/{id}.json` | `GetDecklistController.php:38-54` | public, `Last-Modified` = decklist | Correct. Votes, favorites and comments go through the ORM and bump `dateUpdate`. |
| `/decklists/by_date/{date}.json` | `ListDecklistsByDateController.php:43-44` | public, **no `Last-Modified`** | Never answers 304. |
| `/decklists/top_by_card/{code}.json` | `ListTopDecklistsByCardController.php:41-87` | public, `Last-Modified` = max of the top 10 | Unreliable: the ranking uses `CURRENT_TIMESTAMP` (`:64`) and changes without any date moving. A decklist leaving the top 10 can lower the max. Unknown card → public `[]` without a JSON `Content-Type` (`:53-56`). |
| `/custom-packs/published` | `ListPublishedCustomPacksController.php:54` | bare `JsonResponse`: `no-cache, private`, no CORS, no JSONP | **Could be public.** It does not depend on the user. `UserCustomPack.updatedAt` is available for a `Last-Modified`. |

All of these depend only on the URL (`jsonp` is a query parameter), so no `Vary` is needed. None
of them touches the session.

`isNotModified()` answers 304 when `Last-Modified <= If-Modified-Since`. A list built from the max
`dateUpdate` of its rows therefore keeps answering 304 after a row is removed or a related entity
changes. With `max-age=600` the damage is limited, but the 304s can extend staleness indefinitely
as long as nothing in the date moves.

### `/api/oauth2/deck/load/{id}` (main firewall, anonymous)

`LoadSharedDeckController.php:23` sends no cache headers (`no-cache, private`) and
`Access-Control-Allow-Origin: *`. The output depends only on the deck and its owner's "share my
decks" flag, so it **could** be public with a short TTL. Two caveats:
- turning sharing off only takes effect once cached copies expire;
- the `history` it returns counts unsaved changes. Autosave
  (`DeckBuilder/AutosaveController.php:59-63`) stores them without updating `dateUpdate`, so
  `Last-Modified` = deck date would be wrong.

Leaving it uncached is defensible.

### `/api/private` (main firewall, `ROLE_USER`)

All five routes are private, as they should be. `LoadDeck`, `ListMyDecks` and `ListUserDecks` set
only `Last-Modified`, so Symfony sends `private, must-revalidate`, then `max-age=0` through the
session. `GetCustomPacks` and `UserInfo` set nothing (`no-cache, private`).

Problems:
- **Cross-user 304** on `ListMyDecksController.php:50-55` (`/api/private/decks`) and
  `ListUserDecksController.php` (`/api/private/decks_by_user/{username}`).
  - The URL is the same for every user, and there is no ETag or `Vary: Cookie`.
  - Scenario: user A loads the list. A logs out, and B logs in on the same browser. The browser
    revalidates with A's `If-Modified-Since`. If B's newest deck is older, the server answers 304
    and the browser shows A's list to B.
  - On `decks_by_user/{username}`, the owner also sees private decks. A's private decks can be
    shown to B on that URL.
- The same lists miss deletions (the max does not move) and autosaves (`dateUpdate` does not
  move). `LoadDeckController.php:35-38` has the autosave gap too.
- `UserInfoController.php:102`: `setCallback($jsonp)` is not wrapped, so an invalid callback
  gives a 500 instead of a 400 (as in `JsonpTrait`).

## 4. Recommendations

In this order. Step 2 must not ship before step 1.

1. **Make category B safe:**
   - Search forms: pre-check the owned packs in JavaScript (from the user info), or call
     `setPrivate()` on those three pages.
   - `find` branch: `setPrivate()` when `custom_packs` is non-empty, as `favorites` and `mine` do.
   - `/user/profile/...` and `/user/reviews/...`: add `PUBLIC_ACCESS` rules if they are meant to
     be public, otherwise drop `setPublic()`. Pass `$response` to `render()` in
     `PublicProfileController`.
   - `/deck/import`: drop `setPublic()`.
2. **Stop the layout from starting a session.** Read the flashes only when a session exists:
   `{% if app.request.hasPreviousSession %}` around the three loops (the guard
   `Security/layout.html.twig` already uses). Then, with `curl -D -` as an anonymous visitor:
   - check that category A answers `public, max-age=600`, without `Set-Cookie`;
   - check that B (after step 1) and D answer `private`;
   - check that a logged-in visitor still gets their flashes. Their session cookie makes
     `hasPreviousSession` true, so their pages stay private: acceptable.
3. **Private deck lists:** replace `Last-Modified` on `ListMyDecks` / `ListUserDecks` with an ETag
   built from the user id, the deck ids and their dates. Alternatively, send
   `Cache-Control: no-store` and drop the 304 logic.
4. **Public API:**
   - complete the `Last-Modified` dates (printings in ByPack/Search; pack and cycle dates);
   - run a cheap `MAX(dateUpdate)` query before the heavy one;
   - add `Last-Modified` to `ListDecklistsByDate`;
   - drop `Last-Modified` from `ListTopDecklistsByCard` and rely on `max-age`;
   - give `ListPublishedCustomPacks` the same headers as the other public endpoints;
   - return a 4xx for unsupported formats in `ListCardsByPack`.
5. **Open questions:**
   - What is in front of PHP in prod (reverse proxy, CDN)? Without a shared cache, `public` only
     saves repeat visits from the same browser, and the server load gain is small.
   - If there is one, add `s-maxage` and decide on the TTL per page type. Home and lists change
     often; card pages and reviews do not.
   - Should `LoadSharedDeck` become public with a short TTL?
