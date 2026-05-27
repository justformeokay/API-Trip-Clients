# HealingYuk Backend API

Production-grade REST API for the **HealingYuk** travel and open-trip marketplace platform, built with Native PHP 8.1+, Clean Architecture, and OWASP security practices.

---

## Tech Stack

| Layer         | Technology                                       |
|---------------|--------------------------------------------------|
| Language      | PHP 8.1+ (no framework)                          |
| Architecture  | Clean Architecture + Repository + Service Layer  |
| Database      | MySQL 8.0+ (PDO, prepared statements)            |
| Auth          | JWT (HS256) + opaque refresh token rotation      |
| Password hash | Argon2ID (`PASSWORD_ARGON2ID`)                   |
| Dependencies  | `firebase/php-jwt`, `vlucas/phpdotenv`, `ramsey/uuid` |
| Testing       | PHPUnit 10                                       |
| API Docs      | OpenAPI 3.1 (`docs/openapi.yaml`)                |

---

## Requirements

- PHP 8.1+
- MySQL 8.0+
- Composer 2.x
- Apache (mod_rewrite) or Nginx

---

## Installation

```bash
# 1. Clone
git clone https://github.com/your-org/healingyuk-backend.git
cd healingyuk-backend

# 2. Install PHP dependencies
composer install --optimize-autoloader

# 3. Configure environment
cp .env.example .env
# Edit .env with your DB credentials, JWT secret (min 32 chars), etc.

# 4. Create database
mysql -u root -p -e "CREATE DATABASE healingyuk CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 5. Run migrations (in order)
mysql -u root -p healingyuk < database/migrations/001_create_users_auth_tables.sql
mysql -u root -p healingyuk < database/migrations/002_create_trips_catalog_tables.sql
mysql -u root -p healingyuk < database/migrations/003_create_bookings_payments_tables.sql

# 6. (Optional) Seed sample data
php database/seeders/DatabaseSeeder.php
```

---

## Configuration (`.env`)

| Variable              | Description                                       | Example                    |
|-----------------------|---------------------------------------------------|----------------------------|
| `APP_ENV`             | `production` / `staging` / `development`          | `development`              |
| `APP_URL`             | Public base URL                                   | `https://api.healingyuk.com` |
| `DB_HOST`             | MySQL host                                        | `127.0.0.1`                |
| `DB_PORT`             | MySQL port                                        | `3306`                     |
| `DB_DATABASE`         | Database name                                     | `healingyuk`               |
| `DB_USERNAME`         | DB user                                           | `healingyuk_app`           |
| `DB_PASSWORD`         | DB password                                       | (strong password)          |
| `JWT_SECRET`          | HS256 signing key — **min 32 chars**              | `change_me_…`              |
| `JWT_ACCESS_TTL`      | Access token TTL in seconds                       | `900` (15 min)             |
| `JWT_REFRESH_TTL`     | Refresh token TTL in seconds                      | `2592000` (30 days)        |
| `CORS_ALLOWED_ORIGINS`| Comma-separated allowed origins                   | `https://healingyuk.com`   |

---

## Running Locally

### PHP Built-in Server (development only)

```bash
php -S localhost:8000 -t public public/index.php
```

### Nginx + PHP-FPM

Use the provided `nginx.conf` as a starting template. Point `root` to the `public/` directory.

```bash
cp nginx.conf /etc/nginx/sites-available/healingyuk
nginx -t && systemctl reload nginx
```

---

## API Documentation

Interactive OpenAPI docs are in `docs/openapi.yaml`.

View locally with any OpenAPI tool:
```bash
# Swagger UI via Docker
docker run -p 8080:8080 \
  -e SWAGGER_JSON=/docs/openapi.yaml \
  -v $(pwd)/docs:/docs \
  swaggerapi/swagger-ui
```

Then open `http://localhost:8080`.

---

## Project Structure

```
backend/
├── app/
│   ├── Core/                   # Framework primitives (Router, Container, Request, Response, Config)
│   ├── Domain/
│   │   ├── Entities/           # Immutable value objects (User, Trip, Booking)
│   │   └── Contracts/          # Repository interfaces
│   ├── Infrastructure/
│   │   ├── Database/           # PDO connection wrapper
│   │   └── Repositories/       # MySQL implementations of contracts
│   ├── Services/               # Business logic (AuthService, TripService, BookingService …)
│   ├── Presentation/
│   │   └── Controllers/        # HTTP request handlers
│   ├── Middlewares/            # Auth, CORS, Rate-Limit, Security Headers, Logging
│   ├── Validators/             # Input validation (BaseValidator + domain validators)
│   ├── Exceptions/             # Exception hierarchy + error handler
│   └── Helpers/                # ResponseHelper, PaginationHelper, global functions
├── bootstrap/
│   └── app.php                 # Bootstrap sequence
├── config/                     # PHP config arrays (app, database, jwt, cors, rate_limit)
├── database/
│   ├── migrations/             # SQL migration files (run in order)
│   └── seeders/                # Development data seeder
├── docs/
│   └── openapi.yaml            # OpenAPI 3.1 specification
├── public/
│   ├── index.php               # Entry point
│   └── .htaccess               # Apache rewrite rules
├── routes/
│   └── api.php                 # All route definitions
├── tests/
│   ├── Unit/Services/          # Unit tests
│   ├── Integration/            # Integration tests
│   └── bootstrap.php           # Test bootstrap
├── nginx.conf                  # Nginx configuration template
├── phpunit.xml                 # PHPUnit configuration
├── composer.json
└── .env.example
```

---

## Authentication Flow

```
POST /api/v1/auth/register   → create account (email verification sent)
POST /api/v1/auth/login      → returns { access_token, refresh_token }

# Use access_token as Bearer token (expires in 15 min by default)
Authorization: Bearer <access_token>

# When access_token expires — rotate with refresh token:
POST /api/v1/auth/refresh    { "refresh_token": "..." }
→ returns new { access_token, refresh_token }   (old refresh_token is revoked)

# Logout
POST /api/v1/auth/logout     → revokes current session
POST /api/v1/auth/logout-all → revokes ALL user sessions
```

---

## Geo Search

```
GET /api/v1/trips/search?latitude=-8.4111&longitude=116.4671&radius_km=50
```

Returns trips within 50 km of the given coordinates using the **Haversine formula** with a bounding-box pre-filter for performance.

---

## Running Tests

```bash
# Run all tests
composer test

# Unit tests only
vendor/bin/phpunit --testsuite Unit

# With coverage report
vendor/bin/phpunit --coverage-html coverage
```

---

## Security Highlights

- **Argon2ID** password hashing
- **Refresh token rotation** — each refresh issues a new pair; old token is immediately revoked
- **JWT stored only in memory / httpOnly cookie** — never in localStorage
- **OWASP security headers** on every response (HSTS, CSP, X-Frame-Options, etc.)
- **Rate limiting** per IP + endpoint (token bucket in DB)
- **Parameterized queries** throughout — zero string interpolation in SQL
- **Soft deletes** on all user-facing entities
- **Audit log** for every sensitive action (login, booking, password change)

---

## Seeder Test Credentials

| Role      | Email                        | Password          |
|-----------|------------------------------|-------------------|
| Admin     | admin@healingyuk.com         | Admin@123456      |
| Organizer | organizer@healingyuk.com     | Organizer@123456  |

---

## License

Proprietary — © HealingYuk. All rights reserved.
