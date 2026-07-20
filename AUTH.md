# CMO AI — Authentication (Laravel Breeze + Bootstrap 5)

Complete authentication matching **Laravel Breeze** (Bootstrap stack), adapted for `first_name` / `last_name` user fields.

## Features

| Feature | Route | Controller |
|---------|-------|------------|
| Login | `GET/POST /login` | `AuthenticatedSessionController` |
| Register | `GET/POST /register` | `RegisteredUserController` |
| Logout | `POST /logout` | `AuthenticatedSessionController@destroy` |
| Forgot password | `GET/POST /forgot-password` | `PasswordResetLinkController` |
| Reset password | `GET/POST /reset-password/{token}` | `NewPasswordController` |
| Email verification | `GET /verify-email` | `EmailVerificationPromptController` |
| Profile update | `GET/PATCH /profile` | `ProfileController` |
| Change password | `PUT /password` | `PasswordController` |
| Delete account | `DELETE /profile` | `ProfileController@destroy` |

## Install

```bash
# 1. PHP dependencies (fix SSL if needed — see getcomposer.org/local-issuer)
composer install

# 2. Frontend (Bootstrap 5 via Vite)
npm install
npm run dev

# 3. Environment
cp .env.example .env
php artisan key:generate

# 4. Database
php artisan migrate

# 5. Mail (required for password reset & email verification)
# Set in .env:
# MAIL_MAILER=smtp
# MAIL_HOST=...
# MAIL_PORT=587
# MAIL_USERNAME=...
# MAIL_PASSWORD=...
# MAIL_FROM_ADDRESS=noreply@cmoai.app
```

### Optional: Official Breeze install (if starting fresh Laravel app)

```bash
composer require laravel/breeze --dev
php artisan breeze:install bootstrap
npm install && npm run dev
php artisan migrate
```

This project already includes the Breeze-equivalent files pre-integrated with CMO AI architecture.

## File Structure

```
app/Http/Controllers/Auth/     # Breeze controllers
app/Http/Requests/Auth/       # Login, profile, password requests
routes/auth.php                # All auth routes
resources/views/auth/          # Login, register, forgot/reset, verify
resources/views/profile/       # Profile edit + partials
resources/views/layouts/       # app + guest (Bootstrap 5)
resources/css/app.css          # Bootstrap 5 + CMO AI theme
resources/js/app.js            # Bootstrap JS
```

## User Model

- Implements `MustVerifyEmail` for standard Laravel email verification links
- Fields: `first_name`, `last_name`, `email`, `password`
- Accessor: `full_name`, `initials`, `name` (alias for full_name)

## Middleware

- `guest` — redirect authenticated users to `/dashboard`
- `verified` — Laravel `EnsureEmailIsVerified` (email link, not OTP)

## Default redirect after login

`/dashboard` → links to `/app/dashboard` for the main CMO AI app.
