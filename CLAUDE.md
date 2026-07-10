<!-- wt-418-template: pkg v1 -->
# Working context — pkg-laravel-throttling (`diesellaptops/pkg-laravel-throttling`)

Detailed context for AI agents and developers changing this repo. Pair with the
shared standards in `.local/docs-playbooks/` (session-start, pr-workflow, testing,
review). When this file and a playbook disagree, this file wins.

## Session start (run before any work)

1. Ensure `.local/` exists and is listed in `.gitignore` (create / add if missing).
2. Set up docs-playbooks — if `.local/docs-playbooks/` is missing,
   `git clone https://github.com/diesellaptops/docs-playbooks .local/docs-playbooks`;
   if present, `git -C .local/docs-playbooks fetch` then `pull --ff-only` to keep it current.
3. Open `.local/docs-playbooks/session-start.md` and follow it step by step.

## Purpose & why it exists

A **shared Laravel library** — not a service. It exists so the Diesel Laravel APIs
share one rate-limiting definition instead of each re-implementing a limiter. It
registers a single named rate limiter (`diesel-api`) at boot; consuming apps apply it
via the built-in `throttle:diesel-api` middleware. Distributed as a Composer package
and auto-discovered.

## Architecture overview

Tiny package — the whole library is one service provider plus a config file.

```
src/
  DieselThrottlingProvider.php   the entire library: register() merges config,
                                 boot() publishes config + registers the limiter
config/
  diesel-throttling.php          per_minute + limiter_name (env-backed defaults)
tests/
  TestCase.php                   Orchestra Testbench base; boots the provider
  RateLimiterRegistrationTest.php limiter registration / keying / 429 behavior
composer.json                    name, autoload, extra.laravel.providers (discovery)
phpunit.xml                      testsuite config
```

**How pieces connect:**
- `composer.json` → `extra.laravel.providers` lists `DieselThrottlingProvider`, so a
  consuming Laravel app auto-discovers and boots it.
- `register()` merges `config/diesel-throttling.php` under the `diesel-throttling` key.
- `boot()` reads `limiter_name` (default `diesel-api`) and `per_minute` (default 60),
  then calls `RateLimiter::for($limiterName, fn(Request) => Limit::perMinute(...)->by($key))`.
- The `$key` is `auth_` + `md5()` of: `x-api-key` header, else `Authorization` header,
  else `$request->ip()` — so callers are bucketed per API key / per token / per IP.
- The consumer applies it by adding `throttle:diesel-api` to a route/group.

## Coding conventions

- **PHP 8.3**, PSR-4 (`Diesellaptops\DieselThrottling\` → `src/`). Match the existing
  Laravel/Illuminate idioms (service provider, `RateLimiter::for`, `Limit`).
- **Depend only on the `illuminate/*` components already required** (`support`, `cache`,
  `http`, `config`) — this is a library; do not pull in the full `laravel/framework`.
- **Env reads go in `config/diesel-throttling.php` only**, via `env()`, exposed through
  the `diesel-throttling.*` config keys — never call `env()` from the provider (it
  reads `config()` already, which is config-cache safe in the consuming app).
- **Keep it a library:** no routes, no controllers, no app/HTTP kernel here. The consumer
  owns route wiring.

## What to avoid (footguns)

- **No middleware aliases are registered here.** Despite the name, the provider only
  registers the *named limiter* `diesel-api`; there is no `auth`/`apikey`/`ti2` alias in
  this package. Don't document or assume aliases that don't exist — consumers throttle
  via Laravel's built-in `throttle:diesel-api`.
- **`limiter_name` and the consumer's `throttle:<name>` must match.** If you change
  `PKG_LARAVEL_LIMITER_NAME` (or the config default) the limiter registers under a new
  name and every `throttle:diesel-api` reference in consumers breaks silently (no limit
  applied). Coordinate the rename across all consumers.
- **The limiter bucket key is `md5()` of the API key / Authorization header.** It's a
  cache-key hash for bucketing, not a security control — don't treat it as auth, and
  don't log the raw header.
- **IP fallback** means unauthenticated callers behind a shared NAT share one bucket.
  Be deliberate if you change the keying precedence.
- **Config caching in consumers:** the per-minute value is resolved at provider boot
  from `config()`. If a consumer runs `config:cache`, the env var must be present at
  cache time. Document new knobs in the README table.
- **Releases are tags.** Consumers pin via Composer; bump and tag (current `v2.0.0`)
  rather than relying on branch state.

## Key domain concepts

- **Named rate limiter** — Laravel's `RateLimiter::for(name, closure)`. This package
  registers exactly one (`diesel-api`).
- **Limit key / bucket** — the identity a limit is counted against: `x-api-key` →
  `Authorization` → IP, hashed and `auth_`-prefixed.
- **`per_minute`** — max attempts per bucket per minute (`Limit::perMinute`); default 60.
- **`throttle:<name>` middleware** — the consumer-side hook that activates the limiter.

## Testing approach

- **PHPUnit `^12.5.8|^13.0` via Orchestra Testbench ^11** (`tests/TestCase.php` boots the provider
  in a minimal Laravel app). Run `composer test` or `./vendor/bin/phpunit`.
- `RateLimiterRegistrationTest` asserts: the limiter is registered; keying uses
  `Authorization` then falls back to IP; the configured `per_minute` is honored; and the
  route returns `429` after the cap. The test env sets `per_minute = 3`,
  `cache.default = array`.
- `phpunit.xml` enables `failOnWarning` / `failOnRisky` — keep the suite warning-clean.
- **Passing = green PHPUnit.** Add a test for any new keying or config behavior.

## Common tasks

**Change the default rate limit**
- Edit `per_minute` default in `config/diesel-throttling.php` (or set
  `PKG_LARAVEL_THROTTLING_MIN` in the consumer). Update the README table. Bump the tag.

**Change the limiter name**
- Edit `limiter_name` default (or `PKG_LARAVEL_LIMITER_NAME`). **Then update every
  consumer's `throttle:<name>` middleware** — they must match. Coordinate the release.

**Change the keying / bucketing**
- Logic is the closure in `DieselThrottlingProvider::boot()`. Adjust the
  `x-api-key`/`Authorization`/IP precedence, extend `RateLimiterRegistrationTest`, and
  consider the shared-NAT footgun above.

**Cut a release**
- Ensure tests are green, tag a new semver (`vX.Y.Z`), push the tag; consumers pull via
  `composer update diesellaptops/pkg-laravel-throttling`.

## External dependencies

None at runtime beyond the Laravel app it is installed into. It calls no external
service; it only registers a limiter that uses the consumer app's configured cache
store to count requests.

**Consumed by:** the Diesel Laptops Laravel APIs that add `throttle:diesel-api` to their
routes. `[TODO: enumerate the specific consuming API repos.]`
