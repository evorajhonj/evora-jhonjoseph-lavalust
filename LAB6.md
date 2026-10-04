# Laboratory Exercise 6

Jhon Joseph Evora  
BSIT 3-F2  
MCC2024-00076

LavaLust provides the authenticated JSON API. The separate React project sends HTTP requests to it. Only the API connects to MySQL.

## Setup

Copy `.env.example` to `.env`. Set the database fields, login username/password, and frontend URL. Use separate random values of at least 32 characters for `JWT_SECRET` and `REFRESH_TOKEN_KEY`. Generate each with `php -r "echo bin2hex(random_bytes(32));"`.

Use a dedicated Lab 6 database. Run:

```powershell
php lava migration run
php lava migration seed-admin
php lava migration status
php lava serve --port=8006
```

The login account is created with a hashed password from `AUTH_PASSWORD`. Existing accounts are not overwritten. The local test setup uses `lab6_local` on port `3307`, with login `admin` / `admin123`. `Start-Local.ps1` supplies temporary API signing keys for local use.

## API routes

| Method | Path | Operation |
| --- | --- | --- |
| GET | /index.php/api | API information |
| POST | /index.php/api/login | Login |
| POST | /index.php/api/logout | Logout |
| GET | /index.php/api/products | List products |
| POST | /index.php/api/products | Add product |
| PUT or PATCH | /index.php/api/products/{id} | Update product |
| DELETE | /index.php/api/products/{id} | Delete product |

Login accepts a JSON object with `username` and `password`. Send the returned `token` as `Authorization: Bearer TOKEN` on product and logout requests. Product JSON contains `product_name`, `description`, `price`, and `quantity`. Logout revokes the login session, so its token can no longer perform CRUD. Tokens expire after 15 minutes; log in again after expiration.

## Migrations

```powershell
php lava migration create-migration create_example_table
php lava migration run
php lava migration status
php lava migration rollback
php lava migration rollback-all
php lava migration refresh
```

Rollback and refresh remove database tables. Use them only on a disposable development database. Migration routes are available to CLI requests only.

The installed CLI scans `app/commands`, so the custom command is `app/commands/MigrationCommand.php`. Its class name differs from the framework's `Migration` class to prevent a collision. It reads the second positional argument itself because this CLI passes only the first argument to custom command handlers.

## Render

Deploy the Dockerfile from branch `codex/lab6-products-api`. Set `APP_ENV=production`, `DB_HOST`, `DB_PORT`, `DB_USERNAME`, `DB_PASSWORD`, `DB_DATABASE`, `DB_SSL_CA=/var/www/html/ca.pem`, `JWT_SECRET`, `REFRESH_TOKEN_KEY`, `AUTH_USERNAME`, `AUTH_PASSWORD`, `AUTH_EMAIL`, and `FRONTEND_URL` in Render's environment settings. Do not commit `.env`.

Run migrations and create the login account against Aiven before testing the deployed API. The React static site must set `VITE_API_URL` to the deployed `/index.php/api` URL. Set `FRONTEND_URL` to that site's exact origin; comma-separated local origins may be added for development.

## Submission

1. LavaLust API GitHub repository and branch
2. React GitHub repository
3. Render API URL
4. Frontend URL
5. Screenshots: login, product list, add, edit, delete, and Aiven database
6. Working demonstration

API reference: https://lavalust.netlify.app/docs/libraries/api.html
