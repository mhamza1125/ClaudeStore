# Atelier Hide — B2B leather goods store & wholesale quotation platform

A Laravel 12 monolith for a leather goods manufacturer/wholesaler: a premium storefront, a
server-side quantity pricing engine, a request-for-quotation (RFQ) workflow, a custom
Livewire admin, and a B2B customer portal.

> **"Atelier Hide" is a demo brand.** All seeded products, prices, specifications and images are
> illustrative placeholders, flagged `is_demo` and labelled "Demo product" on the storefront.

## Stack

Laravel 12 · PHP 8.2+ · MySQL 8 (tests also run on SQLite in-memory) · Livewire 3 + Alpine.js ·
Tailwind CSS v4 (Vite) · Laravel Fortify (auth) · spatie/laravel-permission (roles) ·
barryvdh/laravel-dompdf (quotation PDFs) · Intervention Image 3 (optimisation) · database queue ·
local/public disks with optional S3. No Filament, no payment gateway.

## Quick start (local)

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
# MySQL: create the database "claudestore" (root, no password by default)
php artisan migrate --seed          # prints the dev admin/sales/buyer passwords
php artisan storage:link
npm run build                       # or: npm run dev
composer run dev                    # serve + queue worker + logs + vite
```

Open http://localhost:8000. Sign in at `/login` with `admin@atelierhide.test` and the password the
seeder printed (it is random on every seed; development only). Admin is at `/admin`.

The queue worker matters: emails, image optimisation and image-provider jobs are queued.
With `MAIL_MAILER=log`, emails (including the customer's secure quotation link) are written to
`storage/logs/laravel.log`.

## Tests

```bash
php artisan test                                        # SQLite in-memory (phpunit.xml)
DB_CONNECTION=mysql DB_DATABASE=claudestore_test php vendor/bin/phpunit   # against MySQL
```

139 tests cover the pricing precedence and tier boundaries, integer money maths, the
basket, RFQ submission (validation, spam protection, rate limits, attachments, price
snapshots), the full quote lifecycle (pricing, versions, secure links, expiry, acceptance,
conversion), authorisation boundaries, the portal, product/category/CSV/image administration,
the hero carousel, WhatsApp link generation, and the chatbot (endpoint, privacy of its business
context, and the Anthropic SDK request shape via a mocked transport).

## What's where

| Area | Code |
| --- | --- |
| Pricing engine | `app/Pricing/PriceResolver.php`, `QuoteTotals.php`, `app/Support/Money.php` — see [docs/PRICING.md](docs/PRICING.md) |
| Quote basket / RFQ | `app/Services/QuoteBasketService.php`, `QuoteSubmissionService.php`, `app/Http/Requests/QuoteSubmissionRequest.php` |
| Quote lifecycle | `app/Services/QuoteWorkflow.php`, `app/Livewire/Admin/QuoteWorkspace.php`, `app/Http/Controllers/CustomerQuoteController.php` |
| Storefront | `app/Livewire/Store/*`, `resources/views/store`, `resources/views/livewire/store` |
| Admin | `app/Livewire/Admin/*`, `app/Http/Controllers/Admin/*` |
| Portal | `app/Http/Controllers/Account/*`, `resources/views/account` |
| Images | `app/Images/*`, `app/Jobs/*` — see [docs/IMAGES.md](docs/IMAGES.md) |
| Design tokens / themes | `resources/css/app.css`, `resources/js/theme.js`, `resources/views/partials/head.blade.php` |
| Roles & permissions | `database/seeders/RolesAndPermissionsSeeder.php`, `app/Policies/QuotePolicy.php` |

## Roles

| Role | Access |
| --- | --- |
| super-admin | Everything, including staff & roles |
| admin | Everything except staff management |
| sales-rep | Quotes assigned to them or unassigned (price, send, convert); read-only customers |
| customer | Storefront, own/company quotes and orders, portal |

## Key workflows

1. **Browse** → catalogue filters/search → product page with live server-side tier pricing.
2. **Add to Quote** (MOQ and order multiples enforced on the server) → `/quote` basket.
3. **Submit RFQ** (guests allowed) → reference `AH-Q-YYYY-000123`, confirmation page, emails.
4. **Admin** → quote workspace: set negotiated prices/discounts, shipping/tax/charges, validity
   and terms → **Save & send** (PDF + secure link, new version).
5. **Customer accepts/rejects** via the secure link or portal, with explicit confirmation.
   Expired or revised quotes cannot be accepted.
6. **Convert to sales order** (`AH-SO-YYYY-000123`) from the accepted version snapshot.
   Payment is handled offline.

## Documentation

- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — server setup, queue, scheduler, storage, mail, backups
- [docs/ENV.md](docs/ENV.md) — every environment variable
- [docs/PRICING.md](docs/PRICING.md) — pricing precedence and maths
- [docs/IMAGES.md](docs/IMAGES.md) — image workflow and provider integration
- [docs/CHATBOT.md](docs/CHATBOT.md) — AI chatbot setup, privacy and costs
- [docs/STATUS.md](docs/STATUS.md) — implemented features, pending integrations, known limitations
