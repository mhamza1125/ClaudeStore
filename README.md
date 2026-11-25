# Leamate — B2B leather goods store & wholesale quotation platform

A Laravel 12 monolith for a leather goods manufacturer/wholesaler: a premium storefront, a
server-side quantity pricing engine, a request-for-quotation (RFQ) workflow, a custom
Livewire admin, and a B2B customer portal. Brand defaults are Leamate (`config/brand.php`);
"Atelier Hide" was the earlier demo brand and survives only in seeded demo accounts
(`admin@atelierhide.test`, `sales@atelierhide.test`).

> All seeded demo products, prices, specifications and images are illustrative placeholders,
> flagged `is_demo`, labelled "Demo product" on the storefront and left out of the sitemap.

## Stack

Laravel 12 · PHP 8.3+ (required by the locked `spatie/laravel-permission`) · MySQL 8 / MariaDB (tests run on SQLite in-memory) · Livewire 3 + Alpine.js ·
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

The current test count and result are recorded in [docs/STATUS.md](docs/STATUS.md). The tests cover the pricing precedence and tier boundaries, integer money maths, the
basket, RFQ submission (validation, spam protection, rate limits, attachments, price
snapshots), the full quote lifecycle (pricing, versions, secure links, expiry, acceptance,
conversion), authorisation boundaries, the portal, product/category/CSV/image administration,
the homepage banner and slide admin, live search suggestions, the single light theme, SEO (sitemap, robots, noindex, structured data), root page URLs and
redirects, WhatsApp link generation, and the chatbot (endpoint, privacy of its business context,
the Cloudflare Workers AI request/response handling via faked HTTP, and the Anthropic SDK request
shape via a mocked transport).

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
| Design system (one light theme) | `resources/css/app.css`, `resources/fonts/web`, `resources/views/components` — see [docs/DESIGN.md](docs/DESIGN.md) |
| SEO | `app/Http/Controllers/SeoController.php` (`/sitemap.xml`, `/robots.txt`), `resources/views/partials/head.blade.php`, `components/breadcrumbs.blade.php` |
| Demo artwork | `database/seeders/Support/DemoImage.php`; redraw with `php artisan demo:refresh-artwork` (files only) |
| Chatbot | `app/Assistant/*` (providers: Cloudflare default, Anthropic), `app/Http/Controllers/AssistantController.php` — see [docs/CHATBOT.md](docs/CHATBOT.md) |
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
3. **Submit RFQ** (guests allowed) → reference `LM-Q-YYYY-000123` (prefix `BRAND_REFERENCE_PREFIX`,
   default `LM`), confirmation page, emails.
4. **Admin** → quote workspace: set negotiated prices/discounts, shipping/tax/charges, validity
   and terms → **Save & send** (PDF + secure link, new version snapshot). A save from a stale
   screen (someone else changed the quote) is refused without writing.
5. **Customer accepts/rejects** via the secure link or portal, with explicit confirmation. The
   customer always sees the sent version's snapshot, never unsent edits. Expired or revised quotes
   cannot be accepted; "valid until D" means through the end of D in Asia/Karachi.
6. **Convert to sales order** (`LM-SO-YYYY-000123`) from the accepted version snapshot.
   Payment is handled offline.

## Documentation

- [CLAUDE.md](CLAUDE.md) — commands, the no-delete rule, working agreement, where things are
- [docs/HOSTINGER.md](docs/HOSTINGER.md) — production on Hostinger shared hosting (cron, PHP settings, deploy, smoke test)
- [docs/DEPLOYMENT.md](docs/DEPLOYMENT.md) — VPS/managed hosting setup, queue, scheduler, storage, mail, backups
- [docs/B2B.md](docs/B2B.md) — who sees which price under every B2B setting
- [docs/DECISIONS.md](docs/DECISIONS.md) / [docs/OWNER_ACTIONS.md](docs/OWNER_ACTIONS.md) — settled decisions; owner to-dos
- [docs/ENV.md](docs/ENV.md) — every environment variable
- [docs/PRICING.md](docs/PRICING.md) — pricing precedence and maths
- [docs/IMAGES.md](docs/IMAGES.md) — image workflow and provider integration
- [docs/CHATBOT.md](docs/CHATBOT.md) — AI chatbot setup, privacy and costs
- [docs/STATUS.md](docs/STATUS.md) — implemented features, pending integrations, known limitations
