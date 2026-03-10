# Hortifrut Box Management - Backend (MVP)

Setup minimal backend for Laragon (Apache + PHP 8.2 + MySQL 8).

Prerequisites
- PHP 8.2+
- Composer
- MySQL (Laragon provides)

Quick start

1. Copy `.env.example` to `.env` and adjust DB credentials.

2. From `backend` run:

```bash
composer install
```

3. Import migration SQL into MySQL (example using mysql CLI or phpMyAdmin):

```sql
-- run file: backend/database/migrations/001_create_tables.sql
```

Ou rode via scripts (recomendado):

```bash
php tools/create_db.php
php tools/run_migrations.php
php tools/seed.php
```

4. Point your virtual host to `backend/public` (Laragon: add site or use `php -S localhost:8000 -t backend/public` for quick test).

5. Example endpoints

- POST /api/v1/auth/login  {email,password}
- POST /api/v1/compras  create compra
- POST /api/v1/compras/receive  {compra_id}

This is a minimal scaffold with core commission logic and weighted average stock update.
Next steps: create repositories, services for vendas, reports endpoints, middleware (JWT), frontend scaffold.

## E2E local

1. Suba a API local:

```bash
php -S 127.0.0.1:8000 -t public
```

2. Em outro terminal, rode:

```bash
php tools/e2e_test.php
```

Opcional: apontar para outra URL

```bash
E2E_API_BASE=http://127.0.0.1:8080 php tools/e2e_test.php
```
