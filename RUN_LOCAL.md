# Run CMO AI Locally (Windows + XAMPP)

## Current blocker: Composer SSL

`composer install` fails with **curl error 60** (Avast/firewall SSL inspection).

### Fix (pick one)

**Option A — Avast (recommended)**  
1. Open **Avast** → **Menu** → **Settings** → **Protection** → **Core Shields**  
2. **Web Shield** → **Settings** → disable **Scan encrypted connections** (temporarily)  
3. Run `composer install` again  
4. Re-enable scanning after install

**Option B — Update CA bundle in `C:\xampp\php\php.ini`**

```ini
curl.cainfo = "E:\bhaiya mumbai project\laravel\aindracmo\cacert.pem"
openssl.cafile = "E:\bhaiya mumbai project\laravel\aindracmo\cacert.pem"
```

Restart terminal, then run `composer install`.

---

## Full setup (after Composer works)

Open **two terminals** in the project folder:

### Terminal 1 — Frontend (Vite)

```powershell
cd "e:\bhaiya mumbai project\laravel\aindracmo"
$env:NODE_OPTIONS="--use-system-ca"
npm run dev
```

### Terminal 2 — Backend (Laravel)

```powershell
cd "e:\bhaiya mumbai project\laravel\aindracmo"
composer install
copy .env.example .env   # skip if .env already exists
php artisan key:generate
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

---

## URLs

| Page | URL |
|------|-----|
| App | http://127.0.0.1:8000 |
| Admin login | http://127.0.0.1:8000/admin/login |
| User login | http://127.0.0.1:8000/login |
| API | http://127.0.0.1:8000/api/v1 |

**Default admin:** `admin@cmoai.app` / `password`

---

## XAMPP MySQL

1. Start **Apache** + **MySQL** in XAMPP Control Panel  
2. Create database `cmo_ai` in phpMyAdmin  
3. `.env` is preconfigured for XAMPP defaults (`root`, no password)

---

## npm SSL (if needed)

```powershell
$env:NODE_OPTIONS="--use-system-ca"
npm install
```
