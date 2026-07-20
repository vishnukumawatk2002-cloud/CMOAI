# CMO AI — Performance & Security Optimization Guide

This document covers caching, query optimization, queues, image handling, security, and production deployment steps.

---

## Already Implemented in Codebase

| Area | What was done |
|------|----------------|
| **Caching** | `DashboardService` caches stats/charts/activities (5 min TTL); auto-invalidates via `DashboardCacheObserver` |
| **Query optimization** | Monthly charts reduced from ~36 queries to 3 grouped SQL queries; admin stats merged into 2 queries |
| **RBAC caching** | `HasAdminRoles` caches permission slugs per request; admin middleware eager-loads roles |
| **Queue jobs** | `GenerateContentJob` for async AI content generation |
| **Image optimization** | `ImageOptimizer` service (resize, compress, UUID filenames) |
| **Security** | `SecurityHeaders` middleware, API rate limiting, login brute-force protection |
| **Config** | `config/cmo.php` for queue/image/cache toggles |

---

## 1. Caching

### Step 1 — Publish Laravel config (if missing)

```bash
php artisan config:publish cache
php artisan config:publish session
php artisan config:publish queue
php artisan config:publish filesystems
php artisan config:publish database
```

### Step 2 — Install & configure Redis

```bash
# Windows: use Memurai or WSL Redis
# Linux: sudo apt install redis-server

# .env
CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

### Step 3 — Run config cache in production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### Step 4 — Add caching to more modules (optional)

```php
// Example: cache active plans
use Illuminate\Support\Facades\Cache;

$plans = Cache::remember('plans.active', config('cmo.cache.plans_ttl'), fn () =>
    Plan::query()->where('is_active', true)->orderBy('sort_order')->get()
);

// Example: cache settings
$settings = Cache::remember('settings.all', config('cmo.cache.settings_ttl'), fn () =>
    Setting::query()->get()
);
```

Clear on update:

```php
Cache::forget('plans.active');
Cache::forget('settings.all');
```

### Step 5 — Use cache tags (Redis only)

```php
Cache::tags(['admin', 'dashboard'])->remember('stats', 300, fn () => ...);
Cache::tags(['admin', 'dashboard'])->flush();
```

---

## 2. Query Optimization

### Implemented

- **Dashboard charts**: single `GROUP BY DATE_FORMAT(...)` per table
- **Dashboard stats**: combined order count + revenue in one query
- **Eager loading**: `loadMissing('brand')` on content authorization
- **Column selection**: activity feeds select only needed columns

### Recommended next steps

| Location | Action |
|----------|--------|
| `ScheduleController` | Replace `whereHas` with `whereIn('content_item_id', $brandItemIds)` |
| `PlanLimitService` | Reuse `$request->attributes->get('subscription')` from middleware |
| `ResolveApiBrand` | Add `withCount('socialAccounts')` when loading brand |
| All list endpoints | Always use `with()` / `withCount()` for relations shown in API Resources |
| MySQL | Run `EXPLAIN` on slow queries; ensure indexes on `created_at`, `paid_at`, `status` |

### Enable Laravel Debugbar (dev only)

```bash
composer require barryvdh/laravel-debugbar --dev
```

---

## 3. Queue Jobs

### Step 1 — Migrate queue tables

```bash
php artisan migrate
```

Tables: `jobs`, `failed_jobs`, `job_batches` (migration `000021`).

### Step 2 — Configure queue driver

```env
QUEUE_CONNECTION=redis
QUEUE_CONTENT_GENERATION=true
```

Set `QUEUE_CONTENT_GENERATION=false` to run AI generation synchronously (local dev without Redis).

### Step 3 — Run queue worker

```bash
# Development
php artisan queue:work redis --tries=3 --timeout=120

# Production (Supervisor)
```

**Supervisor config** (`/etc/supervisor/conf.d/cmo-ai-worker.conf`):

```ini
[program:cmo-ai-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/aindracmo/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/aindracmo/storage/logs/worker.log
```

### Step 4 — Monitor failed jobs

```bash
php artisan queue:failed
php artisan queue:retry all
```

### Step 5 — Add more queued jobs (recommended)

| Job | Purpose |
|-----|---------|
| `SendEmailVerificationJob` | OTP / verification emails |
| `PublishScheduledPostJob` | Cron-driven post publishing |
| `ProcessBrandLogoJob` | Async image optimization |
| `SyncSocialAccountJob` | OAuth token refresh |

**Scheduler** (`routes/console.php`):

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty')->everyMinute();
Schedule::job(new PublishDueScheduledPostsJob)->everyMinute();
```

---

## 4. Image Optimization

### Service: `App\Application\Services\Media\ImageOptimizer`

```php
use App\Application\Services\Media\ImageOptimizer;

$path = app(ImageOptimizer::class)->storeOptimized(
    $request->file('logo'),
    'brands/logos',
    config('cmo.images.max_width'),
    config('cmo.images.max_height'),
    config('cmo.images.quality'),
);
```

### Step 1 — Publish filesystems config

```bash
php artisan config:publish filesystems
php artisan storage:link
```

### Step 2 — Wire logo upload in BrandController

```php
if ($request->hasFile('logo')) {
    $logoPath = app(ImageOptimizer::class)->storeOptimized(
        $request->file('logo'),
        "brands/{$brand->id}",
    );
    $brand->update(['logo_path' => $logoPath]);
}
```

### Step 3 — Environment tuning

```env
IMAGE_MAX_WIDTH=800
IMAGE_MAX_HEIGHT=800
IMAGE_QUALITY=85
```

### Step 4 — Production (optional)

- Use **Spatie Laravel Media Library** or **Intervention Image** for advanced processing
- Serve images via CDN (S3 + CloudFront)
- Convert to WebP for modern browsers

---

## 5. Security Improvements

### Implemented

| Control | Details |
|---------|---------|
| Security headers | `X-Frame-Options`, `X-Content-Type-Options`, `HSTS` (HTTPS) |
| API rate limiting | 60 req/min global; 10/min auth; 5/min content generate |
| Login throttling | 5 attempts per email+IP (web + API) |
| Admin RBAC | Permission checks on all admin routes |
| Image validation | MIME whitelist, 5MB max, UUID filenames |

### Recommended next steps

```bash
# 1. Production .env
APP_DEBUG=false
APP_ENV=production
SESSION_SECURE_COOKIE=true

# 2. Publish Sanctum config
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"

# 3. CORS (if SPA on separate domain)
php artisan config:publish cors

# 4. Force HTTPS
# In AppServiceProvider or middleware:
URL::forceScheme('https');
```

| Area | Action |
|------|--------|
| **CSRF** | Already on web routes; API uses Sanctum tokens |
| **Mass assignment** | Keep `$fillable` strict on all models |
| **SQL injection** | Use Eloquent/query builder only (already done) |
| **XSS** | Blade `{{ }}` auto-escapes; sanitize rich text if added |
| **Secrets** | Never commit `.env`; rotate keys on deploy |
| **Admin API** | Restrict by IP in production if possible |
| **2FA** | Add for admin accounts (future) |

---

## 6. Performance Enhancements

### Production checklist

```bash
# 1. Optimize Composer autoloader
composer install --optimize-autoloader --no-dev

# 2. Cache everything
php artisan optimize

# 3. Enable OPcache (php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000

# 4. MySQL tuning
# - innodb_buffer_pool_size = 70% of RAM
# - Enable slow query log

# 5. Use PHP 8.3+ with JIT (optional)
opcache.jit=1255
opcache.jit_buffer_size=128M
```

### HTTP / CDN

- Enable **gzip/brotli** on Nginx/Apache
- Cache static assets (Vite build) with long `Cache-Control`
- Use **Cloudflare** or similar CDN for global users

### Database indexes (verify)

```sql
-- Already in migrations; confirm on production:
SHOW INDEX FROM users;
SHOW INDEX FROM orders;
SHOW INDEX FROM subscriptions;
SHOW INDEX FROM content_items;
```

### Laravel Octane (advanced)

For high traffic, consider Laravel Octane with Swoole/RoadRunner:

```bash
composer require laravel/octane
php artisan octane:install
```

---

## Quick Reference — Commands

```bash
# Development
php artisan migrate
php artisan db:seed
php artisan queue:work
npm run build

# Production deploy
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize
php artisan queue:restart

# Clear caches
php artisan optimize:clear
php artisan cache:clear
```

---

## Priority Matrix

| Priority | Task | Effort |
|----------|------|--------|
| P0 | Redis + queue worker running | 1 hour |
| P0 | `php artisan optimize` on deploy | 5 min |
| P1 | Publish missing Laravel configs | 30 min |
| P1 | Wire logo upload with `ImageOptimizer` | 1 hour |
| P2 | Cache plans & settings | 30 min |
| P2 | Scheduled post publishing job | 2 hours |
| P3 | Laravel Horizon for queue monitoring | 2 hours |
| P3 | CDN for assets | 2 hours |
