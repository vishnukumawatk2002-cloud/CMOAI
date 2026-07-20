# CMO AI — Production Deployment Guide

**Stack:** Apache 2.4 · PHP 8.3 · MySQL 8 · Redis (recommended)

**App paths used below:**

| Item | Example |
|------|---------|
| Domain | `https://app.cmoai.com` |
| App root | `/var/www/cmoai` |
| Document root | `/var/www/cmoai/public` |
| Linux user | `www-data` |

---

## Table of Contents

1. [Server Prerequisites](#1-server-prerequisites)
2. [Deploy Application Code](#2-deploy-application-code)
3. [`.env` Configuration](#3-env-configuration)
4. [MySQL 8 Setup](#4-mysql-8-setup)
5. [Apache Virtual Host](#5-apache-virtual-host)
6. [Storage Linking](#6-storage-linking)
7. [Build & Optimize Laravel](#7-build--optimize-laravel)
8. [Queue Worker Setup](#8-queue-worker-setup)
9. [Cron Jobs (Scheduler)](#9-cron-jobs-scheduler)
10. [SSL / HTTPS](#10-ssl--https)
11. [Security Hardening](#11-security-hardening)
12. [Post-Deploy Checklist](#12-post-deploy-checklist)
13. [Updates & Rollback](#13-updates--rollback)

---

## 1. Server Prerequisites

### Install packages (Ubuntu/Debian)

```bash
sudo apt update && sudo apt upgrade -y

sudo apt install -y \
  apache2 \
  mysql-server \
  redis-server \
  git curl unzip \
  php8.3 php8.3-fpm php8.3-cli \
  php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-gd php8.3-intl php8.3-bcmath \
  php8.3-redis php8.3-opcache \
  libapache2-mod-security2 \
  certbot python3-certbot-apache \
  supervisor
```

### Enable Apache modules

```bash
sudo a2enmod rewrite ssl headers proxy_fcgi setenvif
sudo a2enconf php8.3-fpm
sudo systemctl restart apache2
```

### PHP 8.3 production settings

Edit `/etc/php/8.3/fpm/php.ini` and `/etc/php/8.3/cli/php.ini`:

```ini
memory_limit = 256M
upload_max_filesize = 10M
post_max_size = 12M
max_execution_time = 60
date.timezone = Asia/Kolkata

; OPcache (production)
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0

; Security
expose_php = Off
display_errors = Off
log_errors = On
```

Restart PHP-FPM:

```bash
sudo systemctl restart php8.3-fpm
```

### Required PHP extensions for CMO AI

| Extension | Purpose |
|-----------|---------|
| `pdo_mysql` | Database |
| `mbstring`, `xml`, `curl`, `zip` | Laravel core |
| `gd` | Image optimization (`ImageOptimizer`) |
| `redis` | Cache, sessions, queues |
| `opcache` | Performance |
| `bcmath` | Decimal/plan pricing |

Verify:

```bash
php -v
php -m | grep -E 'pdo_mysql|redis|gd|opcache'
```

---

## 2. Deploy Application Code

### Create deploy user & directory

```bash
sudo mkdir -p /var/www/cmoai
sudo chown -R $USER:www-data /var/www/cmoai
```

### Clone / upload project

```bash
cd /var/www/cmoai
git clone <your-repo-url> .
# OR upload via SFTP, then:
git pull origin main
```

### Install dependencies

```bash
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build
```

> **Note:** If `artisan` or `public/` are missing, run `composer install` first — it should exist in a complete Laravel install. Copy from a fresh Laravel 12 skeleton if needed.

### Set permissions

```bash
sudo chown -R www-data:www-data /var/www/cmoai/storage /var/www/cmoai/bootstrap/cache
sudo chmod -R 775 /var/www/cmoai/storage /var/www/cmoai/bootstrap/cache
sudo chmod -R 755 /var/www/cmoai/public
```

---

## 3. `.env` Configuration

### Create production environment file

```bash
cp .env.example .env
php artisan key:generate
```

### Production `.env` template

```env
# ── Application ──────────────────────────────────────────
APP_NAME="CMO AI"
APP_ENV=production
APP_KEY=base64:GENERATED_BY_ARTISAN_KEY_GENERATE
APP_DEBUG=false
APP_URL=https://app.cmoai.com

# ── Logging ────────────────────────────────────────────────
LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAILY_DAYS=14

# ── Database (MySQL 8) ─────────────────────────────────────
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cmo_ai
DB_USERNAME=cmoai_user
DB_PASSWORD=STRONG_RANDOM_PASSWORD_HERE

# ── Redis (cache, session, queue) ──────────────────────────
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

CACHE_STORE=redis
CACHE_PREFIX=cmoai_prod_
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# ── Queue ──────────────────────────────────────────────────
QUEUE_CONNECTION=redis
QUEUE_CONTENT_GENERATION=true

# ── Mail ───────────────────────────────────────────────────
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=noreply@cmoai.com
MAIL_PASSWORD=MAIL_PASSWORD
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@cmoai.com"
MAIL_FROM_NAME="${APP_NAME}"

# ── Sanctum / SPA (if using API from separate frontend) ───
SANCTUM_STATEFUL_DOMAINS=app.cmoai.com,www.cmoai.com

# ── CMO AI tuning ──────────────────────────────────────────
CACHE_PLANS_TTL=3600
CACHE_SETTINGS_TTL=3600
IMAGE_MAX_WIDTH=800
IMAGE_MAX_HEIGHT=800
IMAGE_QUALITY=85

# ── Optional: filesystem (local default) ───────────────────
FILESYSTEM_DISK=local
# For S3: FILESYSTEM_DISK=s3 + AWS_* vars
```

### Secure the `.env` file

```bash
chmod 600 .env
chown www-data:www-data .env
```

### Publish Laravel config (first deploy only)

```bash
php artisan config:publish cache
php artisan config:publish queue
php artisan config:publish session
php artisan config:publish filesystems
php artisan config:publish database
php artisan config:publish logging
```

---

## 4. MySQL 8 Setup

```bash
sudo mysql -u root -p
```

```sql
CREATE DATABASE cmo_ai CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

CREATE USER 'cmoai_user'@'localhost' IDENTIFIED BY 'STRONG_RANDOM_PASSWORD_HERE';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, DROP, INDEX, ALTER, REFERENCES
  ON cmo_ai.* TO 'cmoai_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Run migrations & seeders

```bash
php artisan migrate --force
php artisan db:seed --force
```

> Use `--force` only in production when you intend to apply migrations. Skip `db:seed` on subsequent deploys unless seeding new reference data.

### MySQL 8 tuning (optional)

In `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
innodb_buffer_pool_size = 1G
max_connections = 200
slow_query_log = 1
slow_query_log_file = /var/log/mysql/slow.log
long_query_time = 2
```

---

## 5. Apache Virtual Host

### Create site config

`/etc/apache2/sites-available/cmoai.conf`:

```apache
<VirtualHost *:80>
    ServerName app.cmoai.com
    DocumentRoot /var/www/cmoai/public

    <Directory /var/www/cmoai/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    # Deny access to sensitive paths (belt-and-suspenders)
    <DirectoryMatch "^/var/www/cmoai/(storage|bootstrap/cache|vendor|\.git)">
        Require all denied
    </DirectoryMatch>

    ErrorLog ${APACHE_LOG_DIR}/cmoai-error.log
    CustomLog ${APACHE_LOG_DIR}/cmoai-access.log combined

    # PHP-FPM
    <FilesMatch \.php$>
        SetHandler "proxy:unix:/run/php/php8.3-fpm.sock|fcgi://localhost"
    </FilesMatch>
</VirtualHost>
```

Enable site:

```bash
sudo a2ensite cmoai.conf
sudo a2dissite 000-default.conf
sudo apache2ctl configtest
sudo systemctl reload apache2
```

### `public/.htaccess` (Laravel default)

Ensure `/var/www/cmoai/public/.htaccess` exists:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

---

## 6. Storage Linking

Laravel serves user uploads from `storage/app/public` via a symlink in `public/storage`.

```bash
php artisan storage:link
```

Verify:

```bash
ls -la /var/www/cmoai/public/storage
# Should point to: /var/www/cmoai/storage/app/public
```

### Directory structure after linking

```
public/storage  →  storage/app/public/
                      ├── brands/logos/
                      └── uploads/
```

Ensure upload directories are writable:

```bash
mkdir -p storage/app/public/brands/logos
chown -R www-data:www-data storage/app/public
chmod -R 775 storage/app/public
```

---

## 7. Build & Optimize Laravel

Run on **every production deploy**:

```bash
cd /var/www/cmoai

# Frontend assets
npm ci
npm run build

# Laravel caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Combined shortcut
php artisan optimize
```

### Clear caches before re-caching (during deploy)

```bash
php artisan optimize:clear
php artisan optimize
```

### Restart services after deploy

```bash
sudo systemctl reload php8.3-fpm
sudo supervisorctl restart cmoai-worker:*
php artisan queue:restart
```

---

## 8. Queue Worker Setup

CMO AI uses queues for **AI content generation** (`GenerateContentJob`). Redis is the recommended driver.

### Verify Redis

```bash
redis-cli ping
# PONG
```

### Supervisor config

Create `/etc/supervisor/conf.d/cmoai-worker.conf`:

```ini
[program:cmoai-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/cmoai/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600 --timeout=120
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/cmoai/storage/logs/worker.log
stopwaitsecs=3600
```

Enable and start:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start cmoai-worker:*
sudo supervisorctl status
```

### Monitor failed jobs

```bash
php artisan queue:failed
php artisan queue:retry all
```

### After each deploy

```bash
php artisan queue:restart
sudo supervisorctl restart cmoai-worker:*
```

---

## 9. Cron Jobs (Scheduler)

Laravel's scheduler runs scheduled tasks. Add **one** cron entry for the `www-data` or deploy user:

```bash
sudo crontab -u www-data -e
```

Add:

```cron
* * * * * cd /var/www/cmoai && php artisan schedule:run >> /dev/null 2>&1
```

### What the scheduler can run

Add tasks in `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('cache:prune-stale-tags')->hourly();
// Schedule::job(new PublishScheduledPostsJob)->everyMinute();
```

Verify scheduler:

```bash
php artisan schedule:list
php artisan schedule:run -v
```

> **Important:** Do **not** run `queue:work` via cron. Use Supervisor (Section 8) for continuous queue processing.

---

## 10. SSL / HTTPS

### Option A — Let's Encrypt (Certbot)

```bash
sudo certbot --apache -d app.cmoai.com
```

Certbot auto-configures HTTPS and HTTP→HTTPS redirect.

Auto-renewal test:

```bash
sudo certbot renew --dry-run
```

### Option B — Manual SSL VirtualHost

After obtaining certificates, Apache config becomes:

```apache
<VirtualHost *:443>
    ServerName app.cmoai.com
    DocumentRoot /var/www/cmoai/public

    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/app.cmoai.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/app.cmoai.com/privkey.pem

    # Modern TLS
    SSLProtocol all -SSLv3 -TLSv1 -TLSv1.1
    SSLHonorCipherOrder off
    SSLSessionTickets off

    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"

    <Directory /var/www/cmoai/public>
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/cmoai-ssl-error.log
    CustomLog ${APACHE_LOG_DIR}/cmoai-ssl-access.log combined
</VirtualHost>

# Redirect HTTP → HTTPS
<VirtualHost *:80>
    ServerName app.cmoai.com
    Redirect permanent / https://app.cmoai.com/
</VirtualHost>
```

Update `.env` after SSL is active:

```env
APP_URL=https://app.cmoai.com
SESSION_SECURE_COOKIE=true
```

---

## 11. Security Hardening

### Application level (already in CMO AI)

| Control | Location |
|---------|----------|
| Security headers (X-Frame-Options, nosniff, HSTS) | `SecurityHeaders` middleware |
| API rate limiting | `bootstrap/app.php` + `AppServiceProvider` |
| Login throttling | Web + API `LoginRequest` |
| Admin RBAC | Permission middleware on all admin routes |
| CSRF protection | Web routes (Laravel default) |
| Sanctum API tokens | `/api/v1/*` |

### Server hardening checklist

```bash
# 1. Firewall
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw enable

# 2. Disable directory listing (already in vhost: Options -Indexes)

# 3. Hide server version
# /etc/apache2/conf-available/security.conf
ServerTokens Prod
ServerSignature Off

# 4. Fail2ban for Apache (optional)
sudo apt install fail2ban
```

### File & directory permissions

| Path | Owner | Permission |
|------|-------|------------|
| `/var/www/cmoai` | `deploy-user:www-data` | `755` |
| `storage/`, `bootstrap/cache/` | `www-data:www-data` | `775` |
| `.env` | `www-data:www-data` | `600` |
| `public/` | `www-data:www-data` | `755` |

### Never expose these URLs

Block at Apache level (already in vhost `DirectoryMatch`):

- `/storage/` (except symlinked `public/storage`)
- `/vendor/`
- `/.env`
- `/.git/`

### Production `.env` rules

```env
APP_DEBUG=false          # NEVER true in production
APP_ENV=production
LOG_LEVEL=warning
```

### Database security

- Use dedicated MySQL user with minimal privileges (no `GRANT ALL`)
- Bind MySQL to `127.0.0.1` only
- Strong passwords (20+ chars)

### Redis security

In `/etc/redis/redis.conf`:

```conf
bind 127.0.0.1
requirepass YOUR_REDIS_PASSWORD
```

Update `.env`:

```env
REDIS_PASSWORD=YOUR_REDIS_PASSWORD
```

### Admin panel

- Change default admin password immediately after seed
- Restrict `/admin` by IP if possible (Apache `Require ip`)
- Default seed credentials: `admin@cmoai.app` / `password` — **change on first login**

### Backups

```bash
# Daily MySQL backup cron
0 2 * * * mysqldump -u cmoai_user -p'PASSWORD' cmo_ai | gzip > /backups/cmo_ai_$(date +\%Y\%m\%d).sql.gz

# Keep storage uploads in backup
rsync -av /var/www/cmoai/storage/app/ /backups/storage/
```

---

## 12. Post-Deploy Checklist

| # | Check | Command / URL |
|---|-------|---------------|
| 1 | App loads over HTTPS | `https://app.cmoai.com` |
| 2 | Admin login works | `https://app.cmoai.com/admin/login` |
| 3 | User registration/login | `/register`, `/login` |
| 4 | API health | `GET /up` (Laravel health route) |
| 5 | Storage uploads work | Upload brand logo, verify `public/storage/...` |
| 6 | Queue worker running | `sudo supervisorctl status` |
| 7 | Scheduler registered | `php artisan schedule:list` |
| 8 | Redis connected | `redis-cli ping` |
| 9 | Migrations applied | `php artisan migrate:status` |
| 10 | Debug off | Confirm no stack traces on errors |
| 11 | Mail sending | Trigger password reset email |
| 12 | SSL valid | [SSL Labs test](https://www.ssllabs.com/ssltest/) |

---

## 13. Updates & Rollback

### Standard deploy script

```bash
#!/bin/bash
set -e
cd /var/www/cmoai

git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
sudo supervisorctl restart cmoai-worker:*
sudo systemctl reload php8.3-fpm
echo "Deploy complete."
```

Save as `/var/www/cmoai/deploy.sh`, chmod `+x`.

### Rollback

```bash
git checkout <previous-commit>
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate:rollback --step=1   # if migration caused issue
php artisan optimize
php artisan queue:restart
sudo supervisorctl restart cmoai-worker:*
```

### Maintenance mode

```bash
php artisan down --secret="your-bypass-token"
# Deploy changes...
php artisan up
# Bypass URL: https://app.cmoai.com/your-bypass-token
```

---

## Quick Reference

```bash
# Logs
tail -f storage/logs/laravel.log
tail -f storage/logs/worker.log
tail -f /var/log/apache2/cmoai-error.log

# Clear all caches
php artisan optimize:clear

# Re-cache
php artisan optimize

# Queue
php artisan queue:work redis --tries=3
php artisan queue:failed

# Permissions fix
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## Related Docs

- [OPTIMIZATION.md](./OPTIMIZATION.md) — Caching, query tuning, performance
- [API.md](./API.md) — REST API endpoints
- [AUTH.md](./AUTH.md) — User authentication
- [.env.example](./.env.example) — Environment template
