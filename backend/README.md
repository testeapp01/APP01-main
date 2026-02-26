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

4. Point your virtual host to `backend/public` (Laragon: add site or use `php -S localhost:8000 -t backend/public` for quick test).

5. Example endpoints

- POST /api/v1/auth/login  {email,password}
- POST /api/v1/compras  create compra
- POST /api/v1/compras/receive  {compra_id}

This is a minimal scaffold with core commission logic and weighted average stock update.
Next steps: create repositories, services for vendas, reports endpoints, middleware (JWT), frontend scaffold.
