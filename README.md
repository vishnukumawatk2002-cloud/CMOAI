# CMO AI

## Requirements

- PHP 8.2+
- Composer
- Node.js
- MySQL
- Git

## Installation

```bash
git clone https://github.com/vishnukumawatk2002-cloud/CMOAI.git
cd CMOAI

composer install
npm install

copy .env.example .env

php artisan key:generate

# Configure DB in .env
aindracmodb.sql database file
php artisan migrate
php artisan storage:link
php artisan optimize:clear

npm run dev
php artisan serve
```

Open:

```
http://127.0.0.1:8000
```

## Update

```bash
git pull
composer install
npm install
php artisan migrate
php artisan optimize:clear
```

## Important

- Never commit `.env`
- Use `.env.example`
- Configure API keys in `.env`
