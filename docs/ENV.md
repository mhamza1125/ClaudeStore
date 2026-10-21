# Environment variables

Secrets belong in `.env` (never committed). Values marked *Settings* can also be changed at
runtime in Admin → Settings, which takes precedence over the env default.

## Application

| Variable | Default | Notes |
| --- | --- | --- |
| `APP_NAME` | Atelier Hide | Used by Laravel internals and the mail "from" name |
| `APP_ENV` | local | `production` on live servers (disables demo seeding and password printing) |
| `APP_KEY` | — | `php artisan key:generate`; required |
| `APP_DEBUG` | true | **Must be `false` in production** |
| `APP_URL` | http://localhost | Used in emailed links (secure quotation links) and public storage URLs |
| `LOG_CHANNEL` / `LOG_STACK` / `LOG_LEVEL` | stack / single / debug | Use `daily` and `warning` or higher in production |

## Database

| Variable | Default | Notes |
| --- | --- | --- |
| `DB_CONNECTION` | mysql | MySQL 8 (MariaDB 10.11 also tested). Tests use SQLite in-memory via phpunit.xml |
| `DB_HOST` / `DB_PORT` | 127.0.0.1 / 3306 | |
| `DB_DATABASE` | claudestore | |
| `DB_USERNAME` / `DB_PASSWORD` | root / (empty) | Local dev only. Use a dedicated least-privilege user in production |

## Sessions, cache, queue

| Variable | Default | Notes |
| --- | --- | --- |
| `SESSION_DRIVER` | database | |
| `SESSION_SECURE_COOKIE` | — | Set `true` behind HTTPS |
| `CACHE_STORE` | database | Redis recommended in production |
| `QUEUE_CONNECTION` | database | A worker must run (see DEPLOYMENT.md) |

## Mail

| Variable | Default | Notes |
| --- | --- | --- |
| `MAIL_MAILER` | log | `smtp` in production |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_SCHEME` | — | SMTP credentials from your provider |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | hello@example.com / APP_NAME | Use a verified sending domain (SPF/DKIM) |
| `SALES_NOTIFICATION_EMAIL` | sales@atelierhide.test | *Settings.* Receives new RFQs, responses and wholesale applications |

## Storage

| Variable | Default | Notes |
| --- | --- | --- |
| `FILESYSTEM_DISK` | local | Default disk (not used for media) |
| `MEDIA_DISK_DRIVER` | local | `s3` serves public media (optimised images, logo, spec sheets) from the bucket under `public/` |
| `PRIVATE_DISK_DRIVER` | local | `s3` stores quote attachments and original photos in the bucket under `private/` |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` / `AWS_DEFAULT_REGION` / `AWS_BUCKET` | — | Required when either driver is `s3` |
| `AWS_URL` | — | Public base URL of the bucket or CDN for `public/` objects |
| `AWS_ENDPOINT` / `AWS_USE_PATH_STYLE_ENDPOINT` | — / false | For S3-compatible services (R2, Spaces, MinIO) |

## Brand and commerce defaults

| Variable | Default | Notes |
| --- | --- | --- |
| `BRAND_NAME`, `BRAND_TAGLINE`, `BRAND_EMAIL`, `BRAND_PHONE`, `BRAND_ADDRESS` | demo values | *Settings* |
| `BRAND_INSTAGRAM`, `BRAND_LINKEDIN`, `BRAND_FACEBOOK` | — | *Settings* |
| `BRAND_REFERENCE_PREFIX` | AH | Prefix in `AH-Q-2026-000123` / `AH-SO-…` |
| `STORE_TAX_RATE_BPS` | 0 | *Settings.* Flat tax rate in basis points (825 = 8.25%) |
| `STORE_SHIPPING_FLAT_CENTS` | 0 | *Settings.* Default shipping per quote, in cents |
| `STORE_MIN_ORDER_VALUE_CENTS` | 0 | *Settings.* 0 disables |
| `QUOTE_RATE_LIMIT_PER_HOUR` | 5 | RFQ submissions per IP per hour (plus 2 per minute) |

## Image provider

| Variable | Default | Notes |
| --- | --- | --- |
| `IMAGE_PROVIDER` | null | `null` (manual workflow) or `removebg` |
| `REMOVEBG_API_KEY` | — | Required for `removebg` |

## Seeding

| Variable | Notes |
| --- | --- |
| `SEED_ADMIN_PASSWORD` | Production only: `db:seed` creates `admin@atelierhide.test` with this password. Demo data is never seeded in production. Change the admin email after first login. |
