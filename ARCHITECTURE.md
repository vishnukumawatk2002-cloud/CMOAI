# CMO AI — Laravel 12 Project Architecture

Clean architecture for the CMO AI social media automation platform.

---

## 1. Folder Structure

```
aindracmo/
├── app/
│   ├── Domain/                          # Enterprise business rules (innermost)
│   │   ├── Contracts/Repositories/      # Repository interfaces
│   │   ├── Enums/                       # ContentStatus, SocialPlatform, etc.
│   │   └── Exceptions/                  # PlanLimitExceededException, etc.
│   │
│   ├── Application/                     # Use cases & orchestration
│   │   ├── DTOs/
│   │   │   ├── Auth/
│   │   │   ├── Brand/
│   │   │   └── Content/
│   │   └── Services/
│   │       ├── Auth/                    # AuthService, EmailVerificationService
│   │       ├── Brand/                   # BrandService, PlanLimitService
│   │       └── Content/                 # ContentGenerationService
│   │
│   ├── Infrastructure/                  # External concerns
│   │   ├── Repositories/                # Eloquent repository implementations
│   │   ├── AI/                          # ContentGeneratorInterface + adapters
│   │   └── OAuth/                       # (future) Social OAuth drivers
│   │
│   ├── Http/                            # Presentation layer
│   │   ├── Controllers/
│   │   │   ├── Web/
│   │   │   │   ├── Auth/
│   │   │   │   ├── Marketing/
│   │   │   │   ├── Dashboard/
│   │   │   │   ├── Brand/
│   │   │   │   ├── Content/
│   │   │   │   ├── Schedule/
│   │   │   │   ├── Analytics/
│   │   │   │   └── Onboarding/
│   │   │   └── Admin/
│   │   ├── Middleware/
│   │   ├── Requests/
│   │   │   ├── Web/
│   │   │   └── Admin/
│   │   └── Resources/                   # API JSON transformers (future)
│   │
│   ├── Models/                          # Eloquent persistence models
│   ├── Policies/
│   └── Providers/
│       ├── AppServiceProvider.php
│       ├── AuthServiceProvider.php
│       └── RepositoryServiceProvider.php
│
├── bootstrap/
│   ├── app.php                          # Laravel 12 app bootstrap + middleware aliases
│   └── providers.php
│
├── config/
│   └── auth.php                         # web + admin guards
│
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── schema/cmo_ai_schema.sql
│
├── routes/
│   ├── web.php                          # Public + auth routes
│   ├── web/app.php                      # Authenticated app (/app/*)
│   ├── web/onboarding.php               # Onboarding flow
│   ├── admin.php                        # Admin panel (/admin/*)
│   ├── api.php
│   └── console.php
│
├── resources/views/
│   ├── marketing/                       # Landing page
│   ├── auth/                            # Login, register, verify
│   ├── onboarding/                      # Plan, wizard
│   ├── app/                             # Dashboard, content, schedule, analytics
│   ├── admin/                           # Admin panel views
│   └── components/                      # Blade UI components
│
└── tests/
    ├── Unit/                            # Services, domain logic
    └── Feature/                         # HTTP endpoints
```

### Layer Dependency Rule

```
Http (Controllers) → Application (Services) → Domain (Contracts/Enums)
                              ↓
                    Infrastructure (Repositories, AI, OAuth)
                              ↓
                         Models / DB
```

Controllers never call Eloquent directly — they delegate to Services, which use Repository interfaces.

---

## 2. Route Organization

| File | Prefix | Middleware | Purpose |
|------|--------|------------|---------|
| `routes/web.php` | `/` | `web`, `guest`/`auth` | Landing, login, register, Google OAuth, OTP verify |
| `routes/web/onboarding.php` | `/onboarding` | `auth`, `verified` | Brand create, plan select, setup wizard |
| `routes/web/app.php` | `/app` | `auth`, `verified`, `subscription.active`, `brand.selected` | Dashboard, content, schedule, analytics, brand settings |
| `routes/admin.php` | `/admin` | `admin` | Platform admin panel |
| `routes/api.php` | `/api` | `sanctum` | Future mobile/API clients |

### Named Route Conventions

```
landing                          GET  /
login / register                 Auth
verification.*                   OTP flow
onboarding.brand.*               Brand creation
onboarding.plan.*                Plan selection
onboarding.wizard.*              Setup wizard
app.dashboard                    Main dashboard
app.content.*                    Generate + library
app.schedule.*                   Calendar + bulk schedule
app.analytics                    Analytics
app.brand.*                      Settings + social accounts
admin.login / admin.dashboard    Admin panel
admin.users.* / admin.plans.*    Admin CRUD
```

---

## 3. Controller Structure

Controllers are **thin** — validate input, call a Service, return a View/Redirect/JSON.

| Controller | Responsibility |
|------------|----------------|
| `Web\Auth\AuthController` | Login, register, Google OAuth, OTP, logout |
| `Web\Marketing\LandingController` | Public homepage |
| `Web\Dashboard\DashboardController` | KPI dashboard |
| `Web\Brand\BrandController` | Create brand, switch workspace |
| `Web\Brand\BrandWizardController` | 3-step onboarding wizard |
| `Web\Brand\BrandSettingsController` | Brand profile, voice, assets |
| `Web\Brand\SocialAccountController` | OAuth social connections |
| `Web\Content\ContentController` | AI generate + content library |
| `Web\Schedule\ScheduleController` | Calendar, bulk schedule |
| `Web\Analytics\AnalyticsController` | Performance metrics |
| `Web\Onboarding\PlanController` | Plan selection + subscribe |
| `Admin\AuthController` | Admin login/logout |
| `Admin\DashboardController` | Platform stats |
| `Admin\UserController` | User management |
| `Admin\PlanController` | Plan management |

---

## 4. Middleware Setup

Registered in `bootstrap/app.php`:

| Alias | Class | Purpose |
|-------|-------|---------|
| `guest` | `RedirectIfAuthenticated` | Redirect logged-in users away from auth pages |
| `verified` | `EnsureEmailVerified` | Require OTP email verification |
| `brand.selected` | `EnsureBrandSelected` | Load active brand into request + views |
| `brand.access` | `EnsureBrandAccess` | Authorize brand ownership on route model |
| `subscription.active` | `EnsureSubscriptionActive` | Require valid subscription/trial |
| `plan.limit` | `CheckPlanLimit` | Enforce plan quotas (`:brands`, `:posts_per_month`) |
| `admin` | `AdminAuthenticate` | Separate admin guard authentication |

### Middleware Stack by Area

**App routes:** `web` → `auth` → `verified` → `subscription.active` → `brand.selected`

**Onboarding:** `web` → `auth` → `verified`

**Admin:** `web` → `admin`

---

## 5. Service Classes (Application Layer)

| Service | Methods | Depends On |
|---------|---------|------------|
| `AuthService` | `register()`, `login()`, `findOrCreateFromGoogle()` | UserRepository, EmailVerificationService |
| `EmailVerificationService` | `sendOtp()`, `verify()` | EmailVerification model |
| `BrandService` | `create()`, `switchBrand()`, `currentBrand()` | BrandRepository, PlanLimitService |
| `PlanLimitService` | `assertWithinLimit()` | SubscriptionRepository, BrandRepository |
| `ContentGenerationService` | `generate()` | ContentRepository, ContentGeneratorInterface |

Services contain business orchestration. They do not know about HTTP.

---

## 6. Repository Pattern

### Interfaces (Domain/Contracts/Repositories)

- `UserRepositoryInterface`
- `BrandRepositoryInterface`
- `ContentRepositoryInterface`
- `SubscriptionRepositoryInterface`

### Implementations (Infrastructure/Repositories)

- `EloquentUserRepository`
- `EloquentBrandRepository`
- `EloquentContentRepository`
- `EloquentSubscriptionRepository`

### Binding (RepositoryServiceProvider)

```php
UserRepositoryInterface::class => EloquentUserRepository::class,
BrandRepositoryInterface::class => EloquentBrandRepository::class,
ContentRepositoryInterface::class => EloquentContentRepository::class,
SubscriptionRepositoryInterface::class => EloquentSubscriptionRepository::class,
ContentGeneratorInterface::class => StubContentGenerator::class,
```

Swap `StubContentGenerator` with `OpenAiContentGenerator` in production without changing Services.

---

## 7. Authentication Setup

### User Auth (Guard: `web`)

| Method | Implementation |
|--------|----------------|
| Email + password | `AuthService::login()` |
| Google OAuth | Laravel Socialite → `AuthService::findOrCreateFromGoogle()` |
| Email OTP | 6-digit code via `EmailVerificationService` |
| API (future) | Laravel Sanctum (`auth:sanctum`) |

### Admin Auth (Guard: `admin`)

- Separate `admins` table and `Admin` model
- Routes under `/admin/*`
- Roles: `super_admin`, `support`, `analyst`

### Config

- `config/auth.php` — dual guard setup
- Policies: `BrandPolicy`, `ContentPolicy`

---

## 8. Admin Panel Architecture

```
/admin
├── login                    # Guest admin auth
├── dashboard                # Platform KPIs (users, brands, subscriptions)
├── users/                   # User listing + management
├── plans/                   # Plan CRUD + pricing
├── brands/                  # (future) All brands overview
├── subscriptions/           # (future) Billing management
└── analytics/               # (future) Platform-wide metrics
```

### Admin vs App Separation

| Concern | App (`/app`) | Admin (`/admin`) |
|---------|--------------|------------------|
| Guard | `web` (User) | `admin` (Admin) |
| Layout | App sidebar + brand switcher | Admin sidebar |
| Scope | Single user's brands | All platform data |
| Views | `resources/views/app/` | `resources/views/admin/` |

---

## 9. Setup Instructions

```bash
# 1. Fix SSL if needed, then install dependencies
composer install

# 2. Environment
cp .env.example .env
php artisan key:generate

# 3. Database
php artisan migrate
php artisan db:seed --class=PlanSeeder

# 4. Run
php artisan serve
```

---

## 10. Next Implementation Steps

1. Blade views from HTML mockups (`cmo-*.html`)
2. `OpenAiContentGenerator` replacing stub
3. Social OAuth drivers (Facebook, Instagram, LinkedIn, X)
4. Queue jobs: `PublishScheduledPost`, `RetrainKnowledgeBase`, `SyncAnalytics`
5. Payment integration (Razorpay/Stripe)
6. Admin CRUD for users, plans, subscriptions
