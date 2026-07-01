<!-- wt-418-template: pkg v1 -->
# pkg-laravel-throttling (`diesellaptops/pkg-laravel-throttling`)

Shared Laravel library that registers a single named rate limiter (`diesel-api`) so
Diesel Laptops APIs throttle requests the same way without duplicating limiter logic.
Pulled in as a Composer dependency; auto-discovered via Laravel package discovery and
applied by adding the `throttle:diesel-api` middleware to routes.

## Ownership

| Field           | Detail |
| --------------- | ------ |
| Primary owner   | Max Nesvit (`mnesvit@diesellaptops.com`, `@MaxNesvit`) |
| Secondary owner | Tak Shimamura (`tshimamura@diesellaptops.com`, `@TakShimamura`) |
| Team            | Diesel Laptops Web Team |
| PCI scope       | **No** — a throttling/middleware library; handles HTTP request metadata (headers, IP) only, never card data. |

> **Change control:** the PCI scope field feeds the Authorized Approvers Roster.
> `Yes` → Engineering Lead approval required on every change. `No` repos follow the
> normal review process.
>
> Ownership is also declared in [`.github/CODEOWNERS`](.github/CODEOWNERS)
> (`* @MaxNesvit @TakShimamura`) — the source GitHub uses to auto-assign reviewers.
> This table mirrors it; CODEOWNERS wins on conflict.

## What this package provides

A single Laravel service provider, `DieselThrottlingProvider`
(`src/DieselThrottlingProvider.php`), that:

- **Registers one named rate limiter** via `RateLimiter::for($limiterName, …)` — the
  name defaults to `diesel-api` (`config/diesel-throttling.php`).
- **Keys the limit per identity**, in this precedence (`src/DieselThrottlingProvider.php:31-43`):
  1. `x-api-key` request header → `md5(x-api-key)`
  2. else `Authorization` request header → `md5(Authorization)`
  3. else client IP → `md5($request->ip())`

  The chosen value is prefixed `auth_` and passed to `Limit::perMinute(...)->by($key)`.
- **Applies a per-minute cap** read from config (`per_minute`, default **60**).
- **Publishes its config** under the `diesel-throttling-config` tag.

It does **not** register any middleware *aliases* (no `auth` / `apikey` / `ti2` alias is
defined here) — consumers apply the limiter through Laravel's built-in `throttle`
middleware referencing the limiter name (`throttle:diesel-api`).

## Tech stack

- **Language:** PHP `^8.3` (`composer.json`).
- **Framework:** Laravel 13 component contracts — `illuminate/support`,
  `illuminate/cache`, `illuminate/http`, `illuminate/config` all `^13.0`. Not a full
  app; a library consumed by Laravel 13 apps.
- **Package type:** `library`, license `proprietary` (`composer.json`).
- **PSR-4 autoload:** `Diesellaptops\DieselThrottling\` → `src/`.
- **Laravel auto-discovery:** provider declared in `composer.json` `extra.laravel.providers`.
- **Dev/tooling:** `orchestra/testbench ^11.0`, PHPUnit `^12.5.8|^13.0` (`composer.json`).
- **Current tag:** `v1.0.0`.

## Installation (in a consuming Laravel API)

This package is not on packagist; it is installed from its Git repository.

```bash
# register the repo as a VCS source (once, in the consuming app's composer.json)
composer config repositories.diesel-throttling vcs git@github.com:diesellaptops/pkg-laravel-throttling.git

composer require diesellaptops/pkg-laravel-throttling

# publish the config so the app can tune the limit
php artisan vendor:publish --tag=diesel-throttling-config
```

> The `readme.md` history documents the VCS-repository install above. If the team has
> since moved this package onto the private `php.server.diesellaptops.com` Composer
> registry, point `composer config repositories.*` at that registry instead.
> `[TODO: confirm whether install is via the VCS source above or the private registry.]`

The provider is registered automatically via Laravel package auto-discovery
(`extra.laravel.providers`); no manual `config/app.php` entry is needed.

## Usage (wiring it in a consumer)

After install, apply the limiter to the routes you want throttled
(`routes/api.php` in the consumer):

```php
Route::middleware(['throttle:diesel-api'])->group(function () {
    // throttled routes
});
```

`diesel-api` is the limiter registered by this package. Each caller is bucketed by
`x-api-key`, then `Authorization`, then IP (see "What this package provides").

## Configuration

Config file `config/diesel-throttling.php` (publishable):

| Key (config) | Env var | Default | Purpose |
| --- | --- | --- | --- |
| `per_minute` | `PKG_LARAVEL_THROTTLING_MIN` | `60` | Max requests per identity per minute |
| `limiter_name` | `PKG_LARAVEL_LIMITER_NAME` | `diesel-api` | Name the limiter registers under (must match the `throttle:<name>` middleware in the consumer) |

The package ships **no** `.env` file. These env vars (if used) are set in the
**consuming app's** environment; deployed values come from that app's normal config
source. If you change `limiter_name`, update the consumer's `throttle:<name>`
middleware to match.

## Running tests

```bash
composer install
composer test          # or: ./vendor/bin/phpunit
```

Tests run against **Orchestra Testbench** (`tests/TestCase.php`), which boots the
provider in a minimal Laravel app. Coverage (`tests/RateLimiterRegistrationTest.php`):
the limiter is registered, keys off `Authorization`/IP correctly, honors the configured
per-minute limit, and returns `429` once the cap is exceeded. The test environment sets
`per_minute = 3` and `cache.default = array` (`tests/TestCase.php`).

## Deployment

No deploy target — this is a library, not a service. It has no HTTP endpoint and no
running instance; it is consumed at build time via `composer require` by the Diesel
Laravel APIs. Releases are cut as Git tags (current: `v1.0.0`).

## Consumed by

The Diesel Laptops Laravel APIs that opt into shared throttling pull this in as a
Composer dependency and add `throttle:diesel-api` to their routes.
`[TODO: enumerate the specific consuming API repos.]`
