# Migration Symfony 2.8 → 7.4 / PHP 8.5

Migration notes. Starting point: Symfony 2.8.52, FOSUserBundle 2.0.2, PHP 7.1.33.

The functional tests (`make phpunit`) are the safety net: they must stay green at every step.

## Removing FOSUserBundle / rewriting the Security layer

Covered by `src/AppBundle/Tests/Controller/SecurityControllerTest.php` (registration, email
confirmation, login, remember-me, logout).

### Password hashing

Current algorithm (`security.yml` → `encoders: FOS\UserBundle\Model\UserInterface: sha512`):
`MessageDigestPasswordEncoder` with its default settings, i.e. SHA-512, 5000 iterations,
base64 output (88 characters).

```php
$salted = $password . '{' . $salt . '}';
$digest = hash('sha512', $salted, true);
for ($i = 1; $i < 5000; $i++) {
    $digest = hash('sha512', $digest . $salted, true);
}
$hash = base64_encode($digest);
```

- The salt is per user and stored in the database (`salt` column). FOSUser 2.0 generates it
  with `rtrim(str_replace('+', '.', base64_encode(random_bytes(32))), '=')` (43 characters).
  Existing salts are reused as is: how they were generated does not matter for the migration.
- A single encoder is configured for the whole user class, so every account that can log in
  today has a hash in this format. No extra check on production data is needed.

In Symfony 7.4:

- Declare an identical legacy hasher:
  ```yaml
  security:
      password_hashers:
          legacy_sha512:
              algorithm: sha512
              encode_as_base64: true
              iterations: 5000
          AppBundle\Entity\User:     # (or App\Entity\User once renamed)
              algorithm: auto
              migrate_from:
                  - legacy_sha512
  ```
- The `User` entity must implement `LegacyPasswordAuthenticatedUserInterface` (to expose
  `getSalt()`). **Otherwise the salt is not passed to the hasher and every login fails.**
- Implement `PasswordUpgraderInterface` on the user provider (or repository) so passwords are
  re-hashed with bcrypt/argon as users log in. New hashes have no separate salt (`salt` column
  set to `null`).

### Behaviour to reproduce without FOSUser

- **Login by username OR email**: the current provider is
  `fos_user.user_provider.username_email`. The new user provider must accept both (covered by
  `testLoginWithUsername` / `testLoginWithEmail`).
- **Registration with email confirmation** (`fos_user.registration.confirmation.enabled:
  true`): account created disabled with a `confirmationToken`, email containing the
  `/register/confirm/{token}` link, activation + automatic login, single-use token.
- **Login refused for an unconfirmed account** with the `Account is disabled.` message: the
  login template relies on this `messageKey` to display the `/user/remind/{username}` link.
  In Symfony 7, use a `UserChecker` and keep a recognisable message (or update the template
  and the test).
- **Registration validation**: unique username and email, valid email format, username of at
  least 2 characters, password confirmation, CSRF protection.
- **Remember-me**: 365-day lifetime, `REMEMBERME` cookie.
- **Redirect after login** to the originally requested page, otherwise `index`.
- **FOSUser routes still in use**: `/login`, `/login_check`, `/logout`, `/register/*`,
  `/resetting/*`, `/profile/*` (see `app/config/routing.yml`). Password reset (`/resetting`)
  is not covered by the tests yet.

### Configuration issues found

- `config.yml` declares `fos_user.firewall_name: main`, but the firewall is named `default`.
  It works today (automatic login after confirmation is tested), but it should be fixed in the
  new configuration.
- `app/Resources/FOSUserBundle/views/Registration/checkEmail.html.twig` uses the FOSUser 1.x
  file name; FOSUser 2.0 looks for `check_email.html.twig`. This override is probably ignored
  today (not verified).

## Public API (`/api/public/*`)

Covered by `src/AppBundle/Tests/Controller/ApiControllerTest.php`. Response bodies are compared
to snapshots in `src/AppBundle/Tests/Resources/snapshots/api/`: strict on structure, key order,
value types and `{}` vs `[]`, but not on whitespace or JSON escaping. Status codes and the
`Content-Type`, `Cache-Control`, `Access-Control-Allow-Origin` and `Last-Modified` headers are
checked too, as well as `304 Not Modified` on `If-Modified-Since` and JSONP (`?jsonp=callback`).

Regenerate the snapshots only on purpose, and review the diff:
`docker compose exec -e UPDATE_SNAPSHOTS=1 -u www-data symfony php bin/simple-phpunit`

The private API (`/api/private`) is kept: the site's own JavaScript uses its 4 routes,
authenticated by the regular session cookie (`api_private_load_deck`, `api_private_my_decks`,
`api_private_user_decks` for the builder's multi-deck mode and the deck picker of fellowships and
quest logs, in `app.deck.js` / `app.deck_selection.js`; `api_private_custom_packs` in
`app.ui.js`). Covered by `src/AppBundle/Tests/Controller/ApiPrivateControllerTest.php` (see
"Private API" below).

The OAuth2 API (`/api/oauth2`) is to be removed before migrating: see "OAuth2 server" below.

### Current behaviour pinned by the tests (quirks to keep or fix on purpose)

- `/cards/{pack_code}` is case-insensitive (`core` and `Core` both work).
- `/cards/{pack_code}.xml|xls|xlsx` returns `200` with the plain text body
  `<format> format not supported. Only json is supported.` (`text/xml` for xml, `text/html`
  for xls/xlsx). `/card/{code}.xml` is a `404` (route requirement).
- `/cards/search/{q}` ignores the `jsonp` parameter.
- `/cards/` `Last-Modified` is the most recent `dateUpdate` of the cards **and** of their
  printings.
- `/custom-packs/published` and `/user/info` are not in `ApiController`: they return a
  `JsonResponse` with `Cache-Control: no-cache`/`private` and no CORS header.
- `/user/info` returns `null` for anonymous users.
- Error bodies (404) are not checked: they are the Symfony debug output in the test env.

### To look at during the migration

- JSONP: the callback name is echoed unsanitised into an `application/javascript` response
  (XSS vector). Consider validating it (`^[\w.]+$`) or dropping JSONP in favour of CORS.
- `listDecklistsByDateAction` builds its DQL by string concatenation (`LIKE '$date%'`); it is
  only safe because of the route requirement `\d\d\d\d-\d\d-\d\d`. Use a parameter.
- `/custom-packs/published` is tested with a single published pack (`LoadCustomPackData`).
- The `/cards/` snapshot is ~2 MB (1315 cards).

## Website browsing (read-only)

Covered by `src/AppBundle/Tests/Controller/WebsiteBrowsingTest.php`. HTML pages are compared to
text snapshots in `src/AppBundle/Tests/Resources/snapshots/pages/` (visible text only, one line
per text node, no scripts/styles): strict on displayed content, not on markup or attributes.
Downloads are compared byte for byte; zip archives entry by entry.

### Current behaviour pinned by the tests

- `/questlog/view/{id}/{name}` redirects anonymous visitors to the login page, even for public
  quest logs: the `^/questlog/` access rule has no `view` exception (unlike `^/fellowship/view/`
  and `^/deck/view/`). Probably unintended.
- `/myquestlogs` and a private `/deck/view/{id}` are not covered by `access_control`: the
  controllers answer `403` instead of redirecting to the login page.
- `/decklists/mine` and `/decklists/favorites` are reachable anonymously (empty lists).

### To look at during the migration

- Some GET routes write to the database: `/deck/new` (creates a deck), `/deck/clone/{id}`,
  `/fellowship/publish/{id}`. They should become POST (with CSRF protection).
- `/deck/can_publish/{id}` (`deck_publish`) points to `SocialController::publishAction`, which
  does not exist (500). Dead route to remove.
- Fixed: `Texts::slugify()` relied on catching an iconv error when `//TRANSLIT` is not
  supported (musl, i.e. the Alpine Docker image), but the exception class differs between
  Symfony's and PHPUnit's error handlers. It now checks iconv's return value. With musl, accents
  are dropped from file names instead of transliterated (`Dáin` → `Din`).
- Fixed: zip exports used `tempnam("tmp", "zip")`, relative to the process working directory
  (`web/tmp` under the web server). They now use `%kernel.cache_dir%` and fail explicitly if
  the temporary file cannot be created.
- `slugify()` starts with `preg_replace('[^\w\-]', '-', ...)`: the brackets are taken as regex
  delimiters, so this line does not do what it seems to. Harmless, but worth cleaning up.

## Deck workflow (create / edit / publish)

Covered by `src/AppBundle/Tests/Controller/DeckWorkflowTest.php`. Forms are submitted like the
browser does (the builder's JavaScript serializes the deck as JSON into the hidden `content`
field). Everything the tests create is deleted in `tearDown()`.

### Current behaviour pinned by the tests

- `GET /deck/new` creates an empty `New Deck` (problem `too_few_heroes`, version 0.0) and
  redirects to the builder.
- Each save increments the minor version and records a `deckchange` entry
  `[main added, main removed, side added, side removed]`. Quantities above the deck limit are
  capped silently.
- Publishing creates a decklist at version `<major+1>.0` with a canonical name
  `<slug>-<version>`; the deck moves on to `<major+1>.1`.
- An invalid deck cannot be published: the publish form redirects to the deck page with a
  flash error.
- Another user's deck cannot be edited, saved, or published (`403`).
- `GET /deck/copy/{decklist_id}` copies a decklist into a new deck (version 0.1) whose parent is
  the decklist; publishing that deck creates a decklist whose predecessor is the original one
  ("Derived from" / "Inspiration for").

### To look at during the migration

- Empty decks, production behaviour kept on purpose: the "Cannot import an empty deck" guard
  of `/deck/save` (422 "Cannot save an empty deck." on `/deck/save-ajax`) only rejects a
  `content` whose `main` is missing or decodes to an empty array. The builder sends
  `{"main": {}, ...}`, decoded as a `stdClass` that is never `empty()`, so it can save an empty
  deck; a file import without any card sends `{"main": [], ...}` and is refused. Keep this
  distinction when rewriting the decoding (e.g. with `json_decode(..., true)` both would look
  the same).
- `QuestLogController` decodes deck contents the same way (`(array) json_decode(...)`), and
  refuses quest logs with an empty deck.
- Fixed: text import with a pack name (`1x Aragorn (Core Set)`, the format of the text export)
  crashed with "Unrecognized field: pack": `BuilderController::parseTextImport()` still queried
  `Card.pack`, removed by the card printings refactor. It now looks for a card with a printing
  in that pack. Covered by an export → import round trip of the fixture decks.
- `src/AppBundle/Resources/public/js/directimport.js` is not loaded by any template (the import
  page uses `ui.deckimport.js`). Dead file.
- `/deck/save` and `/decklist/create` have no CSRF protection.
- `/deck/copy/{decklist_id}` writes to the database on GET (see also `/deck/new`).
- Fixed: `POST /decklist/create` with an unknown or missing `deck_id` crashed (`getUser()` on
  null); it now answers `400 Bad Request`.

## Decklist comments

Covered by `src/AppBundle/Tests/Controller/DecklistCommentTest.php`. The comment form is built in
JavaScript (`ui.decklist.js`) and posted with AJAX to `POST /user/comment` (`id`, `comment`); the
server answers with a redirect to the decklist. The tests restore the decklists' counters and
dates in `tearDown()` (the API's `Last-Modified` depends on them).

### Current behaviour pinned by the tests

- The comment is stored as HTML: Markdown rendered, bare URLs turned into links, then purified
  (HTMLPurifier: no `<script>`, no event handlers). `nb_comments`, `date_update` and
  `date_last_comment` of the decklist are updated.
- Notification emails ("[ringsdb] New comment", from `seastan@ringsdb.com` with the commenter's
  name): to the decklist author (`is_notif_author`), to previous commenters
  (`is_notif_commenter`) and to users mentioned as `` `@username` `` (`is_notif_mention`), never
  to the commenter. One email per recipient.
- An empty comment is silently ignored (redirect, nothing saved).
- Only the decklist author can hide/show a comment (`POST /user/hidecomment/{id}/{0|1}`); others
  get `200` with the JSON string "You don't have permission to edit this comment.". Hidden
  comments stay in the page, collapsed.

### To look at during the migration

- Fixed: commenting on an unknown decklist crashed on the final redirect (`getNameCanonical()`
  on null); it now answers `400 Bad Request`.
- BUG: for AJAX requests, `CoreExceptionListener` uses `$exception->getCode()` as the HTTP
  status instead of `getStatusCode()`: every HTTP exception (400, 403, 404...) becomes a `500`
  (with the right JSON message). Affects all AJAX calls, e.g. the comment form.
- No CSRF protection on `/user/comment` and `/user/hidecomment`.
- Emails are sent synchronously during the request, with `\Swift_Message::newInstance()`
  (SwiftMailer, replaced by Symfony Mailer in recent Symfony versions).

## Fellowships

Covered by `src/AppBundle/Tests/Controller/FellowshipWorkflowTest.php`. The deck picker
(`app.deck_selection.js`) fills the hidden `deckN_id` / `deckN_is_decklist` fields of the form;
the tests fill them directly. Publishing a fellowship publishes its decks, which changes the
fixture decks: the tests restore them in `tearDown()`.

### Current behaviour pinned by the tests

- A fellowship holds 1 to 4 decks or decklists; empty slots are compacted (deck numbers 1..n).
  An empty fellowship is refused (`422`).
- Using another user's deck requires them to share their decks (`403` otherwise); the deck is
  then cloned for the current user.
- The publish form offers, for each deck, to publish it as a new decklist or to reuse a matching
  decklist (preselected when the deck is at version x.1 with a published child). Publishing
  replaces all decks by decklists, sets `is_public` and `date_publish`, and redirects to
  `/fellowship/view/{id}/{name_canonical}`.
- Hero conflicts (the same hero in two decks) prevent publishing (flash error).
- A published fellowship cannot be published again; its decks cannot be changed any more, its
  name and description can.
- A fellowship with votes, favorites or comments cannot be deleted; deleting a fellowship keeps
  its decks. `delete_list` takes `ids` as `1-2-3` and skips unknown or foreign ids.
- Another user's fellowship cannot be edited, saved, published or deleted (`403`).

### To look at during the migration

- BUG: "Save and Publish" (`auto_publish`) never publishes: `saveAction` tests
  `empty($fellowship->getDecks())`, and a Doctrine collection object is never `empty()`.
- No CSRF protection on `/fellowship/save`, `/fellowship/publish`, `/fellowship/delete`,
  `/fellowship/delete_list`.
- The fixture fellowship 1 is public but references decks (not decklists): a state the
  application itself does not produce. (It had no `date_publish` either, which Twig displayed as
  the current date: fixed in `LoadFellowshipData`.)

## Private API (`/api/private/*`)

Covered by `src/AppBundle/Tests/Controller/ApiPrivateControllerTest.php`, same approach as the
public API (snapshots in `src/AppBundle/Tests/Resources/snapshots/api/private/`). Requests are
sent with AJAX after logging in, like the site's JavaScript does.

### Current behaviour pinned by the tests

- `/decks`: the user's decklists then decks (descriptions emptied), newest first.
- `/decks_by_user/{username}`: same for the given user, but another user only gets the
  decklists, even if they share their decks (`$show_private_decks` ignores `is_share_decks`,
  commented out).
- `/deck/load/{id}`: a deck of the user, or of a user who shares their decks.
- `/custom-packs`: the user's custom packs.
- Errors (unknown user or deck, deck not shared) are `200` with
  `{"success": false, "error": ...}`.
- With data: `Cache-Control: private, must-revalidate` + `Last-Modified`, and `304` on
  `If-Modified-Since`; without data: `no-cache`, no `Last-Modified`.
- Anonymous: `403` `{"success": false, "message": "Access Denied."}` for AJAX requests (through
  `CoreExceptionListener`), redirect to the login page otherwise.

## OAuth2 server (to be removed)

`FOSOAuthServerBundle` makes RingsDB an OAuth2 server, so that third-party applications can act
on behalf of a user through `/api/oauth2/*`. Inherited from ThronesDB. Plan: remove it before
the migration (the bundle depends on FOSUserBundle and is not maintained for recent Symfony
versions).

What is there today:

- Config: `fos_oauth_server` in `config.yml` (entities `Client`, `AccessToken`, `RefreshToken`,
  `AuthCode`, tables `oauth2_*`; user provider `fos_user.user_manager`), bundle registered in
  `AppKernel`.
- Token endpoints, still active: `/oauth/v2/token` (firewall `oauth_token`, `security: false`)
  and `/oauth/v2/auth` (firewall `oauth_authorize`, with its own login form: routes
  `oauth_server_auth_login` / `oauth_server_auth_login_check`, `SecurityController`). Tokens can
  still be issued if clients are registered in `oauth2_client`.
- Token checking is disabled: the `api_oauth2` firewall (`fos_oauth: true`) is commented out, so
  `/api/oauth2/*` goes through the regular `default` firewall and `access_control` lets
  anonymous users in. Tokens are ignored.
- `Oauth2Controller`:
  - `GET /api/oauth2/decks`: decks of the session user (empty without a session);
  - `GET /api/oauth2/deck/load/{id}`: any deck whose owner shares their decks, to anyone
    (owner check commented out), with `Access-Control-Allow-Origin: *`;
  - `PUT /api/oauth2/deck/save/{id}`: the action is commented out (route to a missing method,
    `500`);
  - `PUT /api/oauth2/deck/publish/{id}`: always `403` "Publishing via API has been disabled.".

Before removing it, check whether anything still calls it: `oauth2_client` /
`oauth2_access_token` rows (`SELECT COUNT(*), FROM_UNIXTIME(MAX(expires_at)) FROM
oauth2_access_token`) and, more reliably, the nginx logs for `/api/oauth2/` and `/oauth/v2/`
(`deck/load` works without a token). If `deck/load` has to survive, move it to `/api/public`.

To remove: the bundle (`AppKernel`, `composer.json`, `config.yml`), the `oauth_token` /
`oauth_authorize` firewalls and the commented `api_oauth2` one, the `^/api/oauth2`
access rule, the `/oauth/v2/*` and `/api/oauth2/*` routes (`routing.yml`, `routing_api.yml`,
`routing_api_oauth2.yml`), `Oauth2Controller`, `SecurityController` and its template
`AppBundle:Security:login.html.twig` (only used by the `oauth_server_auth_login*` routes), the 4
entities and their mappings, and the `oauth2_*` tables.

## Quest logs

Covered by `src/AppBundle/Tests/Controller/QuestlogWorkflowTest.php`. The deck picker fills the
hidden `deckN_id`, `deckN_is_decklist` and `questlogdeckN_content` fields; the tests fill them
directly.

### Current behaviour pinned by the tests

- A quest log stores its own copy of each deck's cards (`questlog_deck.content`, JSON as posted),
  next to the deck (and decklist, with its parent deck) it references, and the player's name
  (prefilled with the deck author's username). Slots are compacted.
- Scenario, date played, difficulty (`normal`, `easy`, `nightmare`; anything else becomes
  `normal`), victory (anything but `no` is a success), score (non numeric becomes 0), name
  (default "Untitled Questlog"), Markdown description.
- Checking "public" sets `is_public` and `date_publish` (on every save of a public quest log).
- Refused: no deck (`422`), a deck without content ("Cannot save a questlog with an empty
  deck", `200`), unknown scenario (`404`).
- A quest log with votes, favorites or comments keeps its decks and visibility (the "public"
  checkbox is disabled); the other fields can still change. It cannot be deleted.
- Another user's deck requires them to share their decks and is cloned; a decklist needs no
  sharing.
- A private quest log is visible to its owner, and to others only if the owner shares their
  decks (`403` otherwise). Anonymous visitors are always redirected to the login page (see
  "Website browsing").
- Another user's quest log cannot be edited, saved or deleted (`403`).

### To look at during the migration

- BUG: the branch of `saveAction` meant for "the referenced deck was deleted" (`deckN_id` = 0
  with a content) reads `deckN_content`, but the form posts `questlogdeckN_content`: such a slot
  is silently dropped.
- The deck contents are decoded with `(array) json_decode(...)` (objects inside), like the deck
  builder does: `{"main": {}}` passes the "empty deck" guard.
- No CSRF protection on `/questlog/save`, `/questlog/delete`, `/questlog/delete_list`.

## Card reviews

Covered by `src/AppBundle/Tests/Controller/ReviewTest.php`. The forms of the card page are
posted with AJAX (`ui.card.js`, `ui.reviews.js`); on error, the JavaScript displays the
`message` of the JSON built by `CoreExceptionListener`.

### Current behaviour pinned by the tests

- Write (`/review/post`): one review per card and per user, and no more reviews than the user's
  reputation; not on a card whose primary printing's pack has no release date. Bare URLs become
  Markdown links; the text is rendered and purified. The 200-character minimum is only checked
  in the browser.
- Validation errors are generic `\Exception`s: `500` with the message in the JSON answer (the
  JavaScript relies on it). Anonymous users get a `403` JSON.
- Edit (`/review/edit`): own reviews only. An empty text returns a plain-text `200` "Your review
  is empty." (not JSON).
- Like (`/review/like`): +1 vote and +1 reputation for the author; once per user; liking your own
  review does nothing (still `success: true`).
- Comment (`/review/comment`): plain text, stored HTML-escaped; updates `date_last_comment`.
- Remove (`/review/remove/{id}`): `ROLE_SUPER_ADMIN` only (the fixture admin, `ROLE_ADMIN`,
  cannot); removes the votes too.

### To look at during the migration

- BUG: review comments are escaped twice (`htmlspecialchars()` when saved, Twig autoescape when
  displayed in `Search/display-card-reviews.html.twig` and `Reviews/reviews.html.twig`): `<b>`
  is shown as `&lt;b&gt;`.
- BUG (JavaScript): the comment form's error handler reads `jqXHR.responseBody.message`
  (`ui.card.js`) instead of `responseJSON`: errors are not displayed.
- For AJAX requests, the `403`/`400` HTTP exceptions of `/review/edit` become `500`s (see
  `CoreExceptionListener` above).
- `/review/remove/{id}` accepts any method, GET included, and has no CSRF protection (nor do
  the other review routes).

## Admin area (`/admin/*`)

Covered (read-only for now) by `src/AppBundle/Tests/Controller/AdminPagesTest.php`: access
control (anonymous redirected to the login page, users `403`, also on write routes), and every
GET page as the fixture `admin` (ROLE_ADMIN): text snapshots in
`src/AppBundle/Tests/Resources/snapshots/pages/admin/`, row counts for the card and card
printing lists (1300+ rows), JSON snapshots for the statistics (`?month=2015-08`, in
`snapshots/api/admin/`).

The write forms are covered by `src/AppBundle/Tests/Controller/AdminWriteTest.php`, on records
created by the test only: the generated CRUD of the 8 reference entities (create → show,
edit → edit, delete → list, through the real forms, CSRF tokens included), scenario encounters,
card force delete, user search, comment hide/delete, decklist delete. Not covered yet: the card
image upload (unused, see below), the scenario import command
(`/admin/command/`, downloads from hallofbeorn.com unless a custom JSON is given) and the
Excel / CSV imports.

### Pending: import tests, waiting for sample files

To be written once representative files are available (to be stored under
`src/AppBundle/Tests/Resources/fixtures/`):

- **CSV import** (`POST /admin/csv/upload`, `CSVController`): fields `code`, `old_code`, `name`
  (the pack) and the file `upfile`. Header line + one card per line, in the format produced by
  `BeornJSONtoRingsDBcsv.py` from a Hall of Beorn JSON export: `pack, type, sphere, position,
  code, name, traits, text, flavor, isUnique, cost, threat, willpower, attack, defense, health,
  victory, quest, quantity, deckLimit, illustrator, octgnid, hasErrata`. Creates or renames the
  pack (new packs go to the `ALeP` cycle, or the last one), creates or updates cards and
  printings. Needed: a real CSV for a new pack, and one updating an existing pack.
- **Excel import** (`POST /admin/excel/upload`, `ExcelController`, PHPExcel): file `upfile`,
  first row = column names, then one card per row. Answers "N cards changed or added". Needed: a
  real file (ideally one produced by `/admin/excel/download`, to test the round trip).
- **Scenario import** (`POST /admin/command/`, `command=scenario`,
  `ScrapBeornScenarioDataCommand`): downloads `http://hallofbeorn.com/LotR/ScenarioDetails/...`
  unless `customjson` is given. Needed: a saved Hall of Beorn scenario JSON, so the test never
  calls the network.

### To look at during the migration

- Fixed: `/admin/user/show/{id}` and the "Block" button crashed, FOSUserBundle 2 having dropped
  the "locked" feature: `User` kept the `$locked` property (the prod database still has the
  column) without accessors. `isLocked()` / `setLocked()` were added.
- BUG, not fixed: blocking a user has no effect, FOSUser 2's `isAccountNonLocked()` always
  returns `true` (pinned by `testBlockedUserCanStillLogIn`). To reimplement with a `UserChecker`
  when replacing FOSUser, or to drop.
- The user's "Date of last update" changes at each login (`last_login`, then Gedmo
  timestampable); it is masked in the admin user page snapshot.
- `StatController` concatenates the `month` query parameter into its SQL
  (`"SELECT '" . $month . "' AS month, ..."`): SQL injection, admin only. Use parameters.
- `/admin/stat` and `/admin/stat_packs` default to last month (`date()`): the tests pass a month.
  `/admin/stat_cards` reads `stat_cards_cache`, filled by the `app:stats:precompute-cards` cron,
  and answers `503` when it is empty.
- Moderation actions are GET routes that write: `/admin/user/toggle_locked/{id}`,
  `/admin/decklist/delete/{id}`, `/admin/comment/toggle_hidden/{id}`,
  `/admin/comment/delete/{id}`.
- The generated CRUD controllers accept any method on `new`/`edit`/`show`; `/admin/command/`
  runs `ScrapBeornScenarioDataCommand` from a form.
- To be removed (probably unused in production, inherited from ThronesDB): the card image upload
  of the admin card form (unmapped `file` field in `CardType`, file move in
  `CardController::updateAction`). It writes `web/bundles/app/images/cards/<code>.png`, but the
  site reads card images from `web/bundles/cards/<code>.png` (and `<image_code>.png` for
  printings, `CardsData`): uploaded images are never displayed. It also keeps the `.png` name
  whatever the actual format. Not tested.
- The generated CRUD controllers use the Symfony 2 form API (`createForm(new XxxType())`,
  `$form->bind($request)`, `'entity'` / `'checkbox'` type names, `getName()`), which is gone in
  recent versions (`createForm(XxxType::class)`, `handleRequest()`, FQCN types,
  `getBlockPrefix()`).
- Deleting reference data still in use (e.g. a cycle with packs, a card in decks) fails on the
  foreign keys with a `500` instead of an error message. `Card` has a "force delete" that
  removes its slots, printings and reviews, with SQL built by concatenation (the id comes from
  the route, `\d+` not enforced).

## Stable order of lists

User content lists were sorted by non-unique keys only (dates, popularity, votes), so ties came
back in an arbitrary order: flaky tests, and duplicated or missing items across pages in
production. An `id` tie-breaker was added (same direction as the main key, or `ASC` for
"oldest first" lists) to: the list methods of `DecklistManager`, `FellowshipManager` and
`QuestLogManager`, the home page lists (`DefaultController`), the review lists
(`DefaultController`, `ReviewController`, `CardsData::get_reviews`), the top decklists by card
(`ApiController`), the private API lists, the quest log lists (`QuestLogController`), the user
comment lists and decklist versions (`SocialController`), custom packs, and
`Decks::getDecksWithSlotsForUser()`.

- Fixed: in the "Hot Topics" lists (`DecklistManager`, `FellowshipManager`, `QuestLogManager`),
  `orderBy('d.nbComments')` replaced `orderBy('nbRecentComments')` instead of adding to it:
  recent comments were ignored. Now sorted by comments of the day, then by number of comments
  (`testHotTopicsFavourRecentComments`, decklists only: the fixtures have a single fellowship
  and a single quest log).

## Profile

Covered by `src/AppBundle/Tests/Controller/UserProfileTest.php`: the site's profile form
(`/user/profile_edit` → `/user/profile_save`) and the FOSUserBundle forms (account
`/profile/edit`, password change `/profile/change-password`, password reset `/resetting/*`),
which have to be reimplemented when FOSUserBundle is removed.

### Current behaviour pinned by the tests

- Profile form: username (unique, checked), email, resume (tags stripped by
  `FILTER_SANITIZE_STRING`), sphere color, notification and sharing checkboxes (unticked =
  false), dark mode (also stored in a non-HttpOnly `dark_mode` cookie for 1 year, to apply the
  theme before the page is loaded). Renaming updates `username_canonical` (FOSUser listener):
  the user logs in with the new name. Redirects to the form with a flash message.
- FOSUser account and password forms require the current password ("The entered password is
  invalid."); password confirmation must match.
- Password reset: an email with a `/resetting/reset/{token}` link; a second request is ignored
  while the first one is recent (`retry_ttl`); no hint when the user does not exist; the token
  is single-use, an unknown or used token is a `404`; after the reset the user is logged in.

### To look at during the migration

- The site's profile form validates neither the email format nor its uniqueness: a duplicated
  email is only stopped by the database's unique index (`500`).
- No CSRF protection on `/user/profile_save`.

## Collection

Covered by `src/AppBundle/Tests/Controller/CollectionTest.php`.

### Current behaviour pinned by the tests

- Owned packs (`/collection/packs/save`): `selected-packs`, a list of `id` / `id:count` tokens,
  stored as is in `user.owned_packs` (anything else than digits, `:`, `,` and `-` is refused
  with a plain-text "Invalid pack selection."). The collection page is rendered directly
  (forward, no redirect).
- Art preferences (`/collection/art/save`, AJAX from the card modal): `{card code: pack code}`
  JSON in `user.art_preferences`, `default` or empty removes the entry, `null` when empty.
- Custom packs: create / edit (cards posted as `cards_json`; unknown cards, duplicates and
  quantities outside 1..9 are skipped; the code is `custom_<id>_<6 hex>`), enable / publish
  toggles, delete, copy of a published pack by another user (JSON). Another user's pack is a
  `404`; the name is required.
- Flash messages are displayed by JavaScript (`app.ui.insert_alert_message(...)` in
  `layout.html.twig`), so they are JSON-encoded in the page source.

### To look at during the migration

- No CSRF protection on the collection and custom pack forms.

## After the migration

- The single text exports (`BuilderController::textexportAction`,
  `SocialController` decklist text export) convert the line endings to CRLF
  (`str_replace("\n", "\r\n", ...)`), the zipped exports do not. Keep CRLF until the migration
  is done, then switch to LF (and regenerate `downloads/deck_1.txt` and
  `downloads/decklist_1.txt`).
- `.gitattributes` stores the test snapshots without line ending conversion (`-text`), so that
  the byte-for-byte comparisons do not depend on each developer's `core.autocrlf`.

## Tests and time

The home page's "Daily Challenge" is picked with `srand(<day number>)` (`DefaultController`): it
is masked in the home page snapshots. Dates written during the tests come from `new \DateTime()` (controllers, `DecklistFactory`) and
from Gedmo timestampable, so the tests restore them in `tearDown()` (decklists, fixture decks)
and only check them loosely. Plan: once on a recent Symfony, use `Symfony\Component\Clock`
(`ClockInterface`, `MockClock` in tests) everywhere, including for the timestampable fields, to
freeze the time in all tests.
