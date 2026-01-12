# pkg-laravel-throttling

Reusable Laravel throttling package for Diesel APIs.

This package provides a shared, environment-configurable rate limiter that can be imported by multiple Laravel services to ensure consistent throttling behavior across APIs.

---

## Features

- Default rate limit of **60 requests per minute**
- Explicit package-scoped environment configuration
- Stable identity hashing based on request headers or IP
- Named rate limiter for easy route integration
- Uniform throttling for all consumers
- No API-specific assumptions

---

## Installation

### 1. Add the repository (private)

```bash
composer config repositories.pkg-laravel-throttling vcs git@github.com:YOUR_ORG/pkg-laravel-throttling.git
```
