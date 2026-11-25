# Environment variables

Secrets belong in `.env` (never committed). Values marked *Settings* can also be changed at
runtime in Admin → Settings, which takes precedence over the env default.

## Application

| Variable | Default | Notes |
| --- | --- | --- |
| `APP_NAME` | Leamate (`.env.example`) | Used by Laravel internals and the mail "from" name |
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
| `QUEUE_CONNECTION` | database | A worker must run (see DEPLOYMENT.md; on shared hosting HOSTINGER.md) |
| `DB_QUEUE_RETRY_AFTER` | 180 | Seconds before a running job is retried; must exceed the longest job timeout (120 s) |

## Security

| Variable | Default | Notes |
| --- | --- | --- |
| `SECURITY_HSTS_MAX_AGE` | 0 (off) | Strict-Transport-Security max-age. Enable only after HTTPS is confirmed; start short (300). See HOSTINGER.md |
| `TRUSTED_PROXIES` | — (trust none) | Comma-separated proxy IPs/CIDRs or `*`. See HOSTINGER.md for how to find the value |

## Mail

| Variable | Default | Notes |
| --- | --- | --- |
| `MAIL_MAILER` | log | `smtp` in production |
| `MAIL_HOST` / `MAIL_PORT` / `MAIL_USERNAME` / `MAIL_PASSWORD` / `MAIL_SCHEME` | — | SMTP credentials from your provider |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | hello@example.com / APP_NAME | Use a verified sending domain (SPF/DKIM) |
| `SALES_NOTIFICATION_EMAIL` | info@leamate.com | *Settings.* Receives new RFQs, responses and wholesale applications |

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
| `BRAND_NAME`, `BRAND_TAGLINE`, `BRAND_EMAIL`, `BRAND_PHONE`, `BRAND_ADDRESS` | Leamate values (`config/brand.php`) | *Settings* |
| `BRAND_INSTAGRAM`, `BRAND_LINKEDIN`, `BRAND_FACEBOOK` | — | *Settings* |
| `BRAND_REFERENCE_PREFIX` | LM | Prefix in `LM-Q-2026-000123` / `LM-SO-…` |
| `STORE_TAX_RATE_BPS` | 0 | *Settings.* Flat tax rate in basis points (825 = 8.25%) |
| `STORE_SHIPPING_FLAT_CENTS` | 0 | *Settings.* Default shipping per quote, in cents |
| `STORE_MIN_ORDER_VALUE_CENTS` | 0 | *Settings.* 0 disables |
| `QUOTE_RATE_LIMIT_PER_HOUR` | 5 | RFQ submissions per IP per hour (plus 2 per minute) |
| `BUSINESS_TIMEZONE` | Asia/Karachi | Calendar for quote validity ("valid until D" = through the end of D here), the 00:15 expiry job and negotiated-price windows. `APP_TIMEZONE` stays UTC (stored timestamps) |

## Image provider

| Variable | Default | Notes |
| --- | --- | --- |
| `IMAGE_PROVIDER` | null | `null` (manual workflow) or `removebg` |
| `REMOVEBG_API_KEY` | — | Required for `removebg` |

## Website chatbot

| Variable | Default | Notes |
| --- | --- | --- |
| `AI_DEFAULT_PROVIDER` | cloudflare | `cloudflare`, `anthropic`, or `null` (offline contact panel). `ASSISTANT_PROVIDER` is still read as a fallback |
| `CLOUDFLARE_ACCOUNT_ID` | — | Cloudflare account ID (dashboard → Workers AI / account home). Server-side only |
| `CLOUDFLARE_API_TOKEN` | — | API token with the **Workers AI** permission (Read is enough to run models). Server-side secret |
| `CLOUDFLARE_DEFAULT_MODEL` | @cf/meta/llama-3.3-70b-instruct-fp8-fast | Workers AI text-generation model ID |
| `CLOUDFLARE_AI_BASE_URL` | https://api.cloudflare.com/client/v4 | Only change for testing or an AI Gateway URL |
| `CLOUDFLARE_AI_TEMPERATURE` | 0.3 | Lower = more factual answers |
| `ANTHROPIC_API_KEY` | — | Needed only when `AI_DEFAULT_PROVIDER=anthropic`; server-side only. See CHATBOT.md |
| `ASSISTANT_MODEL` | claude-opus-5-5 | Claude model ID (Anthropic provider) |
| `ASSISTANT_EFFORT` | low | low / medium / high (Anthropic provider) |
| `ASSISTANT_MAX_TOKENS` / `ASSISTANT_TIMEOUT` | 1024 / 30 | Per reply (all providers) |
| `ASSISTANT_RATE_PER_MINUTE` / `ASSISTANT_RATE_PER_DAY` | 8 / 80 | Per IP |

WhatsApp button settings (number, message) are managed in Admin → Settings, not `.env`.

## Seeding

| Variable | Notes |
| --- | --- |
| `SEED_ADMIN_PASSWORD` | Production only: `db:seed` creates `admin@atelierhide.test` with this password. Demo data is never seeded in production. Change the admin email after first login. |
