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
