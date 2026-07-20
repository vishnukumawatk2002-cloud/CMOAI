# CMO AI — REST API Documentation

Base URL: `/api`

All responses use JSON with a consistent envelope:

```json
{
  "success": true,
  "message": "Optional message",
  "data": {},
  "meta": { "current_page": 1, "per_page": 15, "total": 100 }
}
```

Error responses:

```json
{
  "success": false,
  "message": "Error description",
  "errors": { "field": ["validation message"] }
}
```

## Authentication (Sanctum)

Include the token in requests:

```
Authorization: Bearer {token}
```

Brand-scoped routes also accept:

```
X-Brand-Id: {brand_id}
```

---

## App API — `/api/v1`

### Auth (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/register` | Register user, returns token |
| POST | `/auth/login` | Login, returns token |

**Body:** `email`, `password`, `first_name`, `last_name` (register), `device_name` (optional)

### Auth (Protected)

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/auth/logout` | Revoke current token |
| GET | `/auth/me` | Current user |

### Plans (Public)

| Method | Endpoint | Query Params |
|--------|----------|--------------|
| GET | `/plans` | `search`, `sort`, `direction`, `per_page` |
| GET | `/plans/{id}` | — |

### Profile

| Method | Endpoint |
|--------|----------|
| GET | `/profile` |
| PUT | `/profile` |
| PUT | `/profile/password` |

### Subscription

| Method | Endpoint |
|--------|----------|
| GET | `/subscription` |
| POST | `/subscription` — body: `plan_slug`, `billing_cycle` |

### Brands

| Method | Endpoint | Query Params |
|--------|----------|--------------|
| GET | `/brands` | `search`, `sort`, `direction`, `per_page` |
| POST | `/brands` | — |
| GET | `/brands/{id}` | — |
| PUT | `/brands/{id}` | — |
| DELETE | `/brands/{id}` | — |
| POST | `/brands/{id}/switch` | — |

### Notifications

| Method | Endpoint | Query Params |
|--------|----------|--------------|
| GET | `/notifications` | `unread_only`, `sort`, `per_page` |
| PATCH | `/notifications/{id}/read` | — |
| POST | `/notifications/read-all` | — |

### Brand-scoped (requires subscription + brand context)

| Method | Endpoint | Query Params |
|--------|----------|--------------|
| GET | `/dashboard` | — |
| GET/PUT | `/brand/settings` | — |
| GET | `/content` | `status`, `platform`, `search`, `per_page` |
| POST | `/content/generate` | — |
| GET/PUT/DELETE | `/content/{id}` | — |
| GET/POST/DELETE | `/schedule`, `/schedule/{id}` | `status`, `sort` |
| GET/DELETE | `/social-accounts`, `/social-accounts/{id}` | `platform`, `search` |

---

## Admin API — `/api/admin/v1`

### Auth

| Method | Endpoint |
|--------|----------|
| POST | `/auth/login` |
| POST | `/auth/logout` |
| GET | `/auth/me` |

Default admin: `admin@cmoai.app` / `password`

### Modules

| Module | Endpoints | Permissions |
|--------|-----------|-------------|
| Dashboard | GET `/dashboard` | `dashboard.view` |
| Users | GET/GET/PUT/DELETE `/users` | `users.*` |
| Roles | CRUD `/roles`, GET `/roles/permissions-list` | `roles.*` |
| Permissions | CRUD `/permissions` | `permissions.*` |
| Settings | GET/PUT `/settings` | `settings.*` |
| Plans | CRUD `/plans` | `plans.*` |

All list endpoints support: `search`, `sort`, `direction`, `per_page`, `status` (where applicable)

---

## Setup

```bash
php artisan migrate
php artisan db:seed
```

Migration `000019` creates `personal_access_tokens` for Sanctum.

## Query Parameters (Lists)

| Param | Description |
|-------|-------------|
| `search` | Full-text / LIKE search |
| `sort` | Column name |
| `direction` | `asc` or `desc` |
| `per_page` | Items per page (max 100) |
| `page` | Page number |
