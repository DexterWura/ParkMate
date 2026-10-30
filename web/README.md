# Shipit — Production Laravel Boilerplate

A secure, domain-driven Laravel 13 foundation you can clone and build any app on top of. Auth, payments, subscriptions, and Google sign-in are wired in — your job is to add domains and ship.

Visit `/` when running to see the built-in documentation UI.

## Why this boilerplate?

- **Clone and build** — not a tutorial project; a real starting point for SaaS, APIs, marketplaces, internal tools
- **Domain-driven** — each feature lives in `app/Domains/` and stays isolated
- **Secure by default** — rate-limited auth, security headers, JSON API errors, payment ownership checks
- **Payment-ready** — Stripe, Paynow, Pesepay behind one interface; Cashier for subscriptions
- **Configurable** — toggle features and tune security via `config/boilerplate.php` and `.env`

## Stack

| Layer | Choice |
|-------|--------|
| Framework | Laravel 13 |
| Auth | Sanctum (API tokens) |
| OAuth | Socialite (Google) |
| Subscriptions | Cashier (Stripe) |
| Payments | Stripe, Paynow, Pesepay |
| Architecture | DDD-lite (Domains + Shared + Infrastructure) |

## Requirements

- PHP 8.3+
- Composer
- PDO plus the SQLite or MySQL extension
- The PHP extensions listed by the installer preflight

## Quick start

### 1. Create from this repo

```bash
git clone <your-repo-url> my-app
cd my-app
composer install
php artisan serve
```

Open [http://localhost:8000](http://localhost:8000). On the first request, Shipit redirects to `/install`. The installer creates the application key, configures SQLite or MySQL/MariaDB, runs all Laravel migrations, and creates the first verified user.

To configure the project from the command line instead, copy `.env.example` to `.env`, generate an application key, set the database variables, and run `php artisan migrate`.

### 2. Or merge into a fresh Laravel project

```bash
composer create-project laravel/laravel:^13.0 my-app
# Copy app/, config/, database/, routes/, resources/, bootstrap/, stubs/ into my-app
composer require laravel/sanctum laravel/cashier laravel/socialite
```

## First-run web installation

Deploy the repository with Composer dependencies installed. Prefer pointing the web server document root at the `public/` directory. For XAMPP subdirectory installs (for example `http://localhost/shipit`), the repository root includes an `index.php` and `.htaccess` front controller that boots Laravel and blocks direct access to sensitive paths — visit the folder URL and the installer will run.

The web-server user must be able to write:

- The project root and `.env` when it already exists (required for atomic updates)
- `bootstrap/cache/`
- `storage/` and its subdirectories
- `database/` when using SQLite

Visit the deployed URL and follow the installer. Before accepting any configuration, the preflight checks PHP 8.3, required extensions, the selected PDO driver, and writable paths. Set **Site URL** to the public URL including any subdirectory (for example `http://localhost/shipit`).

For MySQL/MariaDB, the supplied account needs permission to connect to the server, execute `CREATE DATABASE`, and create tables in the selected database. The database name is limited to letters, numbers, and underscores. For SQLite, the installer always uses `database/database.sqlite`; it does not accept an arbitrary filesystem path.

### XAMPP (`http://localhost/shipit`)

1. Use **PHP 8.3+** in XAMPP (Apache and CLI must both report 8.3 or newer).
2. From the project folder run `composer install`.
3. Ensure Apache `mod_rewrite` is enabled and `AllowOverride All` applies to `htdocs` (XAMPP default).
4. Open [http://localhost/shipit](http://localhost/shipit) — you should be redirected to the installer (not a directory listing).
5. Choose **MySQL / MariaDB** with host `127.0.0.1`, database `shipit`, user `root`, and an empty password (typical local XAMPP defaults), then create the owner account.

Shipit uses **Laravel migrations**, not Flyway. The installer runs `php artisan migrate --force` and does not run `DatabaseSeeder`, so the development test account is not created.

When installation succeeds, Shipit writes `storage/app/installed`. Installer routes then return `404`; removing this marker is not a supported reset procedure. Back up `.env` and the database before deployment changes.

## Project structure

```
app/
├── Core/                 # Framework glue: middleware, exceptions, providers
├── Domains/              # Your business modules
│   ├── User/             # Auth, profile, password reset
│   ├── SocialAccounts/   # Google OAuth
│   ├── Billing/          # Payments + subscriptions
│   └── Subscription/     # Plan logic
├── Shared/               # Base classes, contracts, helpers (reuse everywhere)
│   ├── Actions/BaseAction.php
│   ├── Controllers/BaseController.php
│   ├── Repositories/BaseRepository.php
│   └── Contracts/
└── Infrastructure/       # External services (gateways, cache, mail)

routes/domains/           # One route file per domain
config/boilerplate.php    # Central customization point
stubs/domain/             # Template for new domains
```

## Build your app

### Add a new domain

1. Create `app/Domains/YourFeature/` with the standard subfolders:
   `Controllers`, `Models`, `Services`, `Repositories`, `Actions`, `DTOs`, `Requests`, `Resources`, `Policies`, `Events`, `Providers`, `Tests`

2. Copy `stubs/domain/ServiceProvider.stub.php` → `app/Domains/YourFeature/Providers/YourFeatureServiceProvider.php`

3. Register the provider in `app/Core/Providers/DomainServiceProvider.php`

4. Add routes in `routes/domains/your-feature.php`

### Conventions

| Pattern | Use for |
|---------|---------|
| **Action** | Single operation (`RegisterUserAction`) |
| **Service** | Orchestrate multiple actions/repos |
| **Repository** | Database queries (extend `BaseRepository`) |
| **DTO** | Typed data between layers |
| **Resource** | API response shaping |
| **Request** | Validation |
| **Policy** | Authorization |

All API controllers should extend `App\Shared\Controllers\BaseController` for consistent responses.

### Toggle features

In `.env`:

```env
FEATURE_GOOGLE_AUTH=true
FEATURE_SUBSCRIPTIONS=true
FEATURE_PAYMENTS=true
```

### Security settings

```env
API_RATE_LIMIT=60          # General API throttle
API_AUTH_RATE_LIMIT=10     # Login/register throttle (per minute)
AUTH_PASSWORD_MIN=8
SANCTUM_TOKEN_EXPIRATION=   # Minutes, or blank for no expiry
FORCE_HTTPS=false           # Set true in production behind TLS
SECURITY_HEADERS=true
CORS_ALLOWED_ORIGINS=*
```

## Authentication

| Method | Endpoint | Auth |
|--------|----------|------|
| `POST` | `/api/v1/auth/register` | Public (rate limited) |
| `POST` | `/api/v1/auth/login` | Public (rate limited) |
| `POST` | `/api/v1/auth/forgot-password` | Public (rate limited) |
| `POST` | `/api/v1/auth/reset-password` | Public (rate limited) |
| `GET` | `/api/v1/auth/social/google/redirect` | Public |
| `GET` | `/api/v1/auth/social/google/callback` | Public |
| `POST` | `/api/v1/auth/logout` | Sanctum |
| `GET` | `/api/v1/auth/me` | Sanctum |
| `PATCH` | `/api/v1/auth/profile` | Sanctum |
| `POST` | `/api/v1/auth/change-password` | Sanctum |
| `POST` | `/api/v1/auth/email/verification-notification` | Sanctum |

All responses use `{ "success": true|false, "message": "...", "data": {} }`.

## Payments

Two separate systems — don't confuse them:

| System | Interface | Purpose |
|--------|-----------|---------|
| **One-off payments** | `PaymentProcessorInterface` | Stripe Checkout, Paynow, Pesepay |
| **Subscriptions** | `SubscriptionGatewayInterface` | Cashier recurring billing |

```bash
POST /api/v1/payments/initiate
Authorization: Bearer {token}

{
  "gateway": "paynow",
  "amount": 10.00,
  "currency": "USD",
  "email": "customer@example.com",
  "description": "Order #123"
}
```

## Configuration

| File | Purpose |
|------|---------|
| `config/boilerplate.php` | Feature flags, rate limits, security |
| `config/auth.php` | User model, guards |
| `config/sanctum.php` | Token settings |
| `config/cors.php` | CORS origins |
| `config/billing.php` | Subscription plans |
| `config/payments.php` | Gateway credentials |
| `config/services.php` | Google, Stripe keys |

See `.env.example` for all variables.

## Testing & CI

```bash
composer test          # Run PHPUnit
vendor/bin/pint        # Fix code style
vendor/bin/pint --test # Check code style
```

GitHub Actions runs Pint + PHPUnit on push/PR (`.github/workflows/tests.yml`).

### Default test user

Only after manually running `php artisan migrate --seed` (the web installer never runs this seeder):

- Email: `test@example.com`
- Password: `password`

## Upgrading from Laravel 12

Laravel 13 requires **PHP 8.3+**. Update `composer.json` dependencies and run:

```bash
composer update laravel/framework --with-all-dependencies
php artisan migrate
```

Key changes in this boilerplate for Laravel 13:

- `PreventRequestForgery` middleware (replaces `VerifyCsrfToken`) in `config/sanctum.php`
- `config/cache.php` with `serializable_classes => false` for hardened cache security
- PHPUnit 12 and Laravel Tinker 3

See the [official Laravel 13 upgrade guide](https://laravel.com/docs/13.x/upgrade) for full details.

## Production checklist

- [ ] Set `APP_ENV=production`, `APP_DEBUG=false`
- [ ] Set `FORCE_HTTPS=true`
- [ ] Prefer MySQL/MariaDB over SQLite for multi-server production deployments
- [ ] Set `CORS_ALLOWED_ORIGINS` to your frontend domain
- [ ] Add Stripe/Paynow/Pesepay live keys
- [ ] Configure mail driver for verification & password reset
- [ ] Set up queue worker (`php artisan queue:work`)
- [ ] Register Stripe webhook at `POST /stripe/webhook`
- [ ] Run `php artisan config:cache` and `route:cache`

## License

MIT
