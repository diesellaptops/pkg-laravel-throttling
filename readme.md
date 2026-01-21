# pkg-laravel-throttling

Reusable Laravel throttling package for Diesel APIs.

This package provides a shared rate limiter so multiple Laravel APIs can apply throttling in a consistent and centralized way.

---

## Purpose

The goal of this package is to avoid duplicating rate-limiting logic across services.

- Throttling logic lives in one place
- Each API controls its own rate limit via environment configuration
- All requests are throttled the same way

---

## What this package does

- Registers a named Laravel rate limiter
- Limits requests **per identity per minute**
- Reads the rate limit from the environment variable  
  **`PKG_LARAVEL_THROTTLING_MIN`**
- Defaults to **60 requests per minute** if the variable is not set
- Applies uniform throttling to all requests
- Does not differentiate between user types

---

## Installation

```bash
composer config repositories.diesel-throttling vcs git@github.com:diesellaptops/pkg-laravel-throttling.git
composer require diesellaptops/pkg-laravel-throttling
php artisan vendor:publish --tag=diesel-throttling-config
```
