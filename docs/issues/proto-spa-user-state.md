# Client-side user state ("proto-SPA"): what it costs, and what changes without it

Status: analysis. Follows [http-cache.md](http-cache.md). The proposal is to **remove** the
"public HTML + private data through Ajax" architecture rather than restore it.

## Context

RingsDB inherits its architecture from ThronesDB, which took it from NetrunnerDB (circa 2013).
That architecture is a kind of proto-SPA. Every HTML page was meant to be
identical for everyone and publicly cacheable. Everything about the current user was fetched
afterwards in JavaScript: login menu, likes and favorites, author buttons, comment forms,
collection, art preferences, patron status.

[http-cache.md](http-cache.md) shows that this never pays off. Every HTML page already goes out
`private, max-age=0`, because the layout reads the flashbag. Several pages declared public even
render per-user content in PHP. The site pays the full price of the architecture and gets none of
its benefit.

This document lists that price, then describes the application as it would be with ordinary
private pages: the server knows the user and returns a complete page, with the menu wired, the
author buttons present and the user's data loaded.

## 1. How a page loads today

1. **HTML.** Same markup for everyone. The user menu is a disabled icon
   (`layout.html.twig:95-97`). Author buttons are shipped hidden
   (`Decklist/toolbar.html.twig:1,4`; `Fellowship/` and `QuestLog/toolbar.html.twig:4`). Comment
   forms are empty anchors (`<a id="comment-form">`).
2. **Script bundles.** On every page: `extra.js` (1.1 MB: jQuery, Highcharts, ForerunnerDB…) and
   `app.js` (196 KB).
3. **Client card database** (`app.data.js:286-288`, on every page):
   - load the cards and packs from ForerunnerDB (browser storage);
   - then request `/api/public/packs/` (20 KB) and `/api/public/cards/` (1.5 MB, uncompressed by
     the dev server). These answer 304 once `max-age=600` has expired.
4. **User** (`app.user.js:143-151`): `GET /api/private/user/info`.
   - Pages pass `app.user.params` (`decklist_id`, `fellowship_id`, `questlog_id`, `card_id`) to
     get the per-page flags: `is_liked`, `is_favorite`, `is_author`, `can_delete`, `review_id`,
     `review_text`.
   - Without params, the result is cached in `localStorage` for 1 hour.
5. **`start.app`** (`app.ui.js:253-257`) fires once the DOM **and** the card database are ready.
   Then the shared code waits for the user (`app.ui.js:104`):
   - it computes owned packs and copies, and applies the art preferences;
   - for a logged-in user, it requests **`/api/private/custom-packs` on every page**
     (`app.ui.js:179-230`), then triggers `custom_packs_loaded`.
6. **`ui.on_all_loaded`** of the page waits for `app.user.loaded`, then builds the per-user
   parts. For example `ui.decklist.js:297-311`: comment form, author buttons, hide buttons,
   social icons, play simulator.

So on a public view page, what the user can do appears only after **three waits in a row**: the
HTML, the card database, then `user/info`.

## 2. The cost

### 2.1 UX

- **The user menu stays disabled until `user/info` answers.** The icon is greyed out and does
  nothing. It is instant only on a localStorage hit, and only for logged-in users on pages without
  params.
- **Owner and per-user controls appear late, after the card database.** This covers the author
  Edit and Delete buttons, the comment form and the "Write a review" button, which pop in after
  the card database and `user/info`. On a first visit (empty local database) that means after
  1.5 MB of JSON. The page moves while the user reads it.
- **Elements that are wrong first, then fixed.**
  - Like, favorite and comment icons are clickable `<a>` for everyone, with handlers bound at DOM
    ready (`ui.decklist.js:9-10`). They are downgraded to `<span>` afterwards for anonymous users,
    the author, or someone who already liked. Same pattern in `ui.fellowshipview.js:95-116` and
    `ui.questlogview.js:94-115`.
  - The play simulator shows the "patreon" overlay to everyone, then hides it for patrons
    (`app.play_simulator.js:10-13`).
  - Dark mode comes from a cookie mirrored by JavaScript. On a new device, or once the cookie is
    gone, the light theme shows and then switches.
- **Anonymous visitors see actions that fail.** "Add a comment" and like on reviews are shown and
  bound for everyone (`display-card-reviews.html.twig:16-18,37-40`, `ui.reviews.js:60-64`).
- **No JavaScript (or a JavaScript error) means no account features.** There is no menu, no
  comment form and no author button: these exist only in JavaScript.
- **"Your comment … will appear on the site in a few minutes"** (`ui.decklist.js:149`, `ui.fellowshipview.js:78`, `ui.questlogview.js:77`, `ui.card.js:41,111`, `ui.reviews.js:34`). The message
  assumes a page cache that does not exist.

### 2.2 Requests and server load

| Visitor | Extra requests on each page view, beyond the HTML |
|---|---|
| Anonymous | `user/info` → **403** (82-byte JSON): the firewall rejects the request (`^/api/private` requires `ROLE_USER`) and `CoreExceptionListener` turns the exception into JSON for Ajax requests. `app.user.js:28` ignores the `Forbidden` error and rejects `app.user.loaded`. The request is cheap (no controller, no query), but it is a full Symfony boot for an answer known in advance. `user.anonymous()` wipes the cache, so this happens **on every page view** of every anonymous visitor, crawlers with JavaScript included. Without the `X-Requested-With` header (curl, `ApiControllerTest::testUserInfoAnonymous`), the same URL answers a 302 to `/login` instead. |
| Logged in, page without params | `user/info` once per hour (localStorage), plus **`/api/private/custom-packs` on every page**, even where it is useless (home, about…). |
| Logged in, view page (decklist, fellowship, quest log, card) | `user/info` with params **on every view**: user query, 2 `COUNT`s, and on the card page **all the card's reviews** loaded to find the user's (`UserInfoController.php:84-95`). Plus `custom-packs`. |
| Everyone | `/api/public/cards/` and `/api/public/packs/` revalidated every 10 minutes, plus loading 1,300 cards into ForerunnerDB on every page, including pages that show no card. |

The server loads the session and the user on the HTML request anyway: the session is used on
every page because of the flashbag. The per-user flags would cost the same queries rendered
inline, **without a second request, a second boot of Symfony and a second firewall pass**.

### 2.3 Correctness bugs that come from the architecture

**The 1-hour localStorage cache** (`app.user.js:39-73`):
- **Page flags leak into the cache.** `user.update` stores the whole response, including
  `is_author`, `is_liked`, `can_delete` and `review_text` from the last page viewed.
  `app.deck.js:394` then reads `is_author` on pages that have no params, so an unpublished deck is
  linked or not depending on the last entity viewed. Each page with params also pushes the expiry
  back.
- **Logging out by any path other than the menu link does not wipe the cache.** Only the link's
  `onclick` wipes it (`app.user.js:107`). The other paths are a direct `/logout`, an expired
  session or remember-me cookie, a locked account, and a logout from another browser. In all of
  them the "logged in" menu, dark mode and collection stay for up to 1 hour.
- **Changing account.** If A logs out by one of those paths and B logs in, B sees A's menu, dark
  mode, owned packs and art preferences on pages without params for up to 1 hour. The login page
  never wipes the cache.
- **Art preferences.** `app.card_modal.js:153-164` saves them on the server but not in the cache.
  The old preferences come back on the next pages.
- **Changes from another device are invisible for 1 hour** (dark mode, collection, donation).
  Only `profile_edit` and the page shown after saving the collection force a reload
  (`app.user.forceReload`).
- **Any `user/info` error is treated as a logout.** A 500 or a network error calls
  `user.anonymous()`. It wipes the cache, writes `dark_mode=0` and shows "Login or Register" to a
  logged-in user.

**Races between `user/info`, the card database and the page code.** Whether the localStorage
cache is cold or warm changes the result:
- `app.ui.js:104` runs the ownership calculation in `app.user.loaded.always`, then `app.ui.js:235`
  calls `on_all_loaded` synchronously. When `user/info` is still in flight, the page code runs
  first and sees `owned` / `owned_copies` undefined. The consequences:
  - **deck builder**: the Sets filter is built with every pack unchecked, and it is never rebuilt
    (`ui.deckedit.js:177-214,1019`). The ownership badges are missing until the deck changes;
  - **deck view**: missing badges (`ui.deckview.js:112-121`);
  - **fellowship and quest log edit**: `show_conflicts` falls back to 3 core sets
    (`app.deck_selection.js:134-145`);
  - **"My quest logs"**: the "owned quests only" filter is never applied (`ui.questlogs.js:163-169`).
- **Play simulator at DOM ready** (`ui.deckedit.js:997`, `ui.deckview.js:98`). It reads
  `app.user.data.donation` before `user/info` has answered, so a patron gets the overlay for good
  on a cold cache. On a public decklist, an anonymous visitor never gets the simulator initialized
  (`ui.decklist.js:304-305`, `.done` only).
- **Custom packs arrive last.** Badges already rendered are not refreshed.
  `app.ui.js:179-230` has no failure handler.

**Semantic mismatches.**
- On fellowship and quest log pages, `is_author` means "author of the fellowship", but
  `app.deck.js:391-398` uses it to decide whether to link the *decks*.
- `app.deck_selection.js:135-141` reads the core set count with `/1-2/` and `/1-3/`. That regex
  also matches `11-2`, and it ignores the current `id:count` format, which `app.ui.js:110-117`
  does handle.

### 2.4 Complexity and code

- **The same per-user information exists two ways.**
  - Fellowship and quest log views already compute `is_owner` in PHP
    (`ViewFellowshipController.php:19`, `ViewQuestlogController.php:19`). Their author buttons are
    still double-gated: Twig `{% if is_owner %}` **and** the `hidden` class, removed by JavaScript
    on `is_author`.
  - The search forms pre-check owned packs in Twig (`SearchDecklistController.php:33-34`…), while
    the custom packs arrive in JavaScript.
  - Collection and profile edit are rendered by Twig, with a `forceReload` workaround to resync
    the JavaScript cache.
- **Markup written in JavaScript strings.** The comment forms (`ui.decklist.js:123-169`,
  `ui.fellowshipview.js:55-93`, `ui.questlogview.js:53-…`) and the menu
  (`app.user.js:101-118`) are HTML concatenated by hand, outside Twig: no escaping and no `path()`.
- **Code to maintain:**
  - `app.user.js` (152 lines, including `display_ads`, now a no-op);
  - `UserInfoController` (107 lines, with 6 SQL queries in strings);
  - the `setup_social_icons`, `add_author_actions`, `setup_comment_form` and
    `setup_comment_hide` functions, copied three times (decklist, fellowship, quest log);
  - the ownership block in `app.ui.js` (~130 lines);
  - the `app.user.params` parameters in 5 templates;
  - 3 dead templates (`templates/Quest/*.twig`, no controller renders them).
- **A request lifecycle that is hard to follow.** Three `$.Deferred` objects plus two events
  (`data.app`, `start.app`, `app.user.loaded`, `custom_packs_loaded`). Their order depends on the
  browser cache, which is the source of the races above.
- **Tests.** The per-user behaviour of the view pages lives only in JavaScript, so PHPUnit cannot
  test it. Tests check `user/info` as JSON (`ApiControllerTest`, `CollectionTest`,
  `UserProfileTest`, `SecurityControllerTest`), not what the user sees.

### 2.5 HTTP cache hazards

As long as the pages are declared public while partly per-user, any fix to the flashbag would
turn them into leaks ([http-cache.md](http-cache.md), section 2.B). The decision to restore the
public cache depends on PHP code being disciplined forever. Making all HTML pages private removes
that whole class of bugs.

## 3. What would change

Target: every HTML page is private (`Cache-Control: private`, the default once the controllers
stop calling `setPublic()`). Twig receives the user through `app.user` and the per-page flags from
the controller.

| Area | Today | With server-rendered private pages | Code removed or changed |
|---|---|---|---|
| **User menu** (every page) | Disabled icon, filled by `user/info` (or the localStorage cache) | Rendered by Twig from `app.user`: Login/Register, or Edit account / Public profile / Log out. Usable on first paint. | `layout.html.twig:95-97`; `app.user.js` `dropdown` / `update` / `anonymous`; the `onclick="app.user.wipe()"` |
| **Dark mode** | Cookie mirrored by JavaScript; inline head script; flash on a new device | `<html class="dark-mode">` rendered from `app.user.darkMode`. Anonymous visitors stay light. | `app.user.apply_dark_mode`, the `<head>` script, the cookie set in `SaveProfileController`; the home promo (`index.html.twig:10,299-307`) becomes `{% if app.user and not app.user.darkMode %}` |
| **`/api/private/user/info`** | Called on every page (anonymous: a 403, every time) | **Removed.** The data is in the page. | `UserInfoController`, `UserInfoDto`, its tests and snapshots |
| **localStorage user cache** | 1 h, the source of the stale-state bugs (2.3) | **Removed.** The session is the only source of truth: logout, account change and changes from another device apply on the next page. | `app.user.retrieve/store/wipe`, `forceReload` (`Collection/packs.html.twig:6-10`, `User/profile_edit.html.twig:4-6`), `reloaduser` in `SavePacksController` |
| **Decklist view** | Like, favorite and comment icons clickable then downgraded; Edit/Delete `display:none` then shown; comment form built in JavaScript; hide buttons added in JavaScript | The controller computes `is_author`, `is_liked`, `is_favorite`, `can_delete`. Twig renders icons in the right state, the author buttons, the comment form (or "log in to comment") and the hide buttons. | `ui.decklist.js` `setup_social_icons`, `add_author_actions`, `setup_comment_form`, `setup_comment_hide` (the Ajax POSTs stay); `app.user.params.decklist_id`; `ViewDecklistController` drops `setPublic()` |
| **Fellowship and quest log views** | `is_owner` already in PHP, but buttons `hidden` until `is_author`; same JavaScript duplicates as decklist | Same as decklist; the `is_owner` already computed is enough for the buttons | `ui.fellowshipview.js` / `ui.questlogview.js`, the same 4 functions; the `hidden` class in `toolbar.html.twig:4`; `app.user.params.*_id` |
| **Comment POSTs, likes, favorites** | Ajax | **Unchanged** (Ajax POST on a server-rendered page). The comment can show up immediately; "will appear in a few minutes" goes. | Message in `ui.*.js` |
| **Card page** | "Write/Edit review" injected after `user/info`; `review_text` sent through JSON; comment and like links shown to anonymous visitors | The controller finds the user's review (a single targeted query, instead of loading every review). Twig renders the right button and the pre-filled form, and hides the actions from anonymous visitors. | `ui.card.js:6-18,30,83-85,173-180`; `app.user.params.card_id`; `UserInfoController.php:84-95` |
| **Reviews list** | Comment and like shown to everyone | Rendered only for logged-in users | `Reviews/reviews.html.twig`, `ui.reviews.js:60-64` |
| **Play simulator** | Patreon overlay shown, then hidden in JavaScript; race at DOM ready; not initialized for anonymous visitors | Twig renders the overlay or not from `app.user.donation`. JavaScript initializes it without depending on the user. | `app.play_simulator.is_patron`; `Builder/play-simulator.html.twig:2,7,10`; the `.done` in `ui.decklist.js:304-305` |
| **Collection, art preferences, custom packs** (builder, deck view, card modal, edit pages) | `owned_packs` and `art_preferences` from `user/info`, then `/custom-packs` on **every page**, after the card database; races in 5 places | The page embeds what it needs in JSON (`<script>app.user = {{ … \|json_encode }}</script>`): owned packs, art preferences, custom packs. **Only on pages that use them** (builder, deck view, edit pages, card modal). Available synchronously before `start.app`, so the races disappear. | `app.ui.js:104-230` (reduced to the calculation, no Ajax, no Deferred); `api_private_custom_packs` (removed if no other caller); `app.deck_selection.js:135-141` aligned on the shared parser |
| **Search forms** | Owned packs pre-checked in Twig on a page marked public; custom packs added later in JavaScript | Private page; owned packs and custom packs rendered together by Twig | `ui.decklist_search.js` / `ui.fellowship_search.js` / `ui.questlog_search.js` (`custom_packs_loaded` section); `setPublic()` in the 3 Search controllers |
| **`setPublic()` / `setMaxAge()`** | ~20 HTML controllers, ineffective | Removed from all HTML controllers. The `favorites` / `mine` `setPrivate()` calls become pointless. The `$cacheExpiration` argument stays only for the public API. | All of `src/Controller` outside `API/`; the `$cacheExpiration` binding |
| **Layout flashbag** | Problem for the cache | **No longer a problem**: the page is private anyway | none |
| **Private APIs `my_decks`, `user_decks`, `load_deck`** | Used by the deck picker and multi-deck mode | **Unchanged.** Real interactions, on demand. The cross-user 304 fix ([http-cache.md](http-cache.md), §3) still applies. | none |
| **Public API** | Its own cache, independent of all this | **Unchanged** | none |
| **Dead code** | `templates/Quest/*`, `app.user.display_ads` | Removed | — |
| **Tests** | Per-user state testable only through `user/info` JSON | Testable in PHPUnit on the HTML (author buttons, comment form, menu, review) | `ApiControllerTest` (user/info) replaced by page tests |

### What stays in JavaScript

- **Interactions:** Ajax POSTs (like, favorite, comment, review, art preferences), the deck
  builder, the simulators, the deck picker.
- **The client card database**, for the builder, tooltips and the card modal. Its cost (loaded on
  every page, 1.5 MB on first visit) is a separate question; see "Not covered" below. Without this
  change, though, it **no longer blocks** the user-dependent display: user state arrives with the
  HTML, not after `start.app`.

### What we give up

- **Shared caching of HTML pages for anonymous visitors**, which does not exist today (see
  [http-cache.md](http-cache.md)). If it is ever wanted, it can come back in a targeted way, for
  example `public` + `s-maxage` only for requests without a session cookie, on a few pages (home,
  card pages). The server would then render the anonymous version, with no JavaScript machinery.
- **One HTML page per user.** That is already the case, since every page is private and
  `max-age=0`.

## 4. Suggested order

Each step can ship on its own.

1. **Menu and dark mode in Twig.**
   - Render `#login` from `app.user`.
   - Keep `app.user.js` only to feed the remaining consumers.
   - Stop calling `user/info` for anonymous visitors (`{% if app.user %}`), which removes the
     403 on every anonymous page view.
2. **View pages (decklist, fellowship, quest log, card):**
   - per-user flags computed by the controller;
   - Twig renders the icons, buttons, forms and review;
   - remove `app.user.params`;
   - remove `setPublic()` on these pages.
3. **Collection, art preferences, custom packs as inline JSON**, only on the pages that use them.
   Remove the `custom-packs` call on every page and the races of `app.ui.js`.
4. **Remove** `UserInfoController`, the localStorage cache, `forceReload`, the remaining
   `setPublic()` calls, `templates/Quest/*` and `display_ads`.
5. **Tests:** cover the HTML of each view page as anonymous, logged-in non-author and author.

## Not covered

- **The client card database on every page.** `extra.js` (1.1 MB), ForerunnerDB, and
  `/api/public/cards/` loaded on pages that show no card. It is the other half of the proto-SPA,
  and deserves the same critique in its own document: which pages really need it (builder, card
  search, modals), and what server-side rendering of the cards would cost.
- **Measurements.** The figures above come from the dev environment, where responses are not
  compressed. A logged-in measurement on a prod copy (page load time until the menu is usable,
  number of requests) would back the argument up.
