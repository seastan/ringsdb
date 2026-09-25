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

The private (`/api/private`) and OAuth2 (`/api/oauth2`) APIs are not covered: the plan is to
drop them before migrating.

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
- `/custom-packs/published` has no fixture data, so only the empty response is tested.
- The `/cards/` snapshot is ~2 MB (1315 cards).
