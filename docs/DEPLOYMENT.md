# Deployment

Targets a single VPS (Ubuntu 22.04/24.04) or managed Laravel hosting (Forge, Ploi, Laravel Cloud,
etc.). The application is a standard Laravel monolith: web server + PHP-FPM, MySQL, one queue
worker and the scheduler.

## Requirements

- **PHP 8.3+** (the locked `spatie/laravel-permission` 8.3 requires it; `composer install` fails on 8.2) with `pdo_mysql`, `mbstring`, `intl`, `gd`, `zip`, `fileinfo`, `openssl`, `curl`
- MySQL 8 (MariaDB 10.11 also works)
- Node 20+ (build step only)
- Composer 2
- An SMTP provider for email
- Optional: an S3-compatible bucket, Redis

## First deploy

```bash
git clone <repo> /var/www/leamate && cd /var/www/leamate
composer install --no-dev --optimize-autoloader
npm ci && npm run build
cp .env.example .env            # then edit: APP_ENV=production, APP_DEBUG=false, APP_URL, DB_*, MAIL_*
php artisan key:generate
php artisan migrate --force
SEED_ADMIN_PASSWORD='<strong password>' php artisan db:seed --force   # roles, groups, admin, Leamate categories (no demo data in production)
php artisan storage:link
php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache
```

Directory permissions: the PHP user must be able to write `storage/` and `bootstrap/cache/`.
Point the web root at `public/`. Force HTTPS and set `SESSION_SECURE_COOKIE=true`.

### Production .env checklist

| Variable | Production value |
| --- | --- |
| `APP_NAME` | `Leamate` |
| `APP_ENV` / `APP_DEBUG` | `production` / `false` (never `true`: it exposes stack traces and config) |
| `APP_URL` | `https://<your-domain>`. Used in emails, signed links and canonical URLs; must not be `127.0.0.1` or `localhost` |
| `APP_KEY` | from `php artisan key:generate` (also signs image URLs; changing it changes every image URL) |
| `DB_*` | production database credentials (not `root` with an empty password) |
| `MAIL_MAILER` + `MAIL_HOST`/`PORT`/`USERNAME`/`PASSWORD` | real SMTP. With `log` nothing is delivered: no verification, contact or quote emails |
| `MAIL_FROM_ADDRESS` | `info@leamate.com` (a domain with SPF/DKIM set up at your mail provider) |
| `SALES_NOTIFICATION_EMAIL` | inbox for quote requests, contact messages and new-account alerts (default `info@leamate.com`) |
| `SESSION_SECURE_COOKIE` | `true` (HTTPS only) |
| `QUEUE_CONNECTION` | `database` (default) or `redis`, with a worker running (see below) |
| `LOG_LEVEL` | `warning` or `error` |
| `CLOUDFLARE_*` | only if the chatbot is enabled |

Brand and contact details default to Leamate (`config/brand.php`); Admin → Settings overrides
them without a deploy.

Want demo content on a staging server? Run `php artisan db:seed --class=DemoCatalogSeeder`
explicitly. Do not do this in production.

## Subsequent deploys

```bash
php artisan down --render="errors::503"
git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize
php artisan queue:restart
php artisan up
```

> **Always rebuild front-end assets after pulling.** `public/build` is not committed. If
> `npm run build` is skipped, pages load the previous CSS/JS bundle against new markup — for
> example, the floating WhatsApp and chat buttons lose their positioning and stack in the top-left
> corner. If the layout looks broken after a deploy, check that `public/build/manifest.json` is
> newer than the pulled commit.

> **Upgrading an existing installation (once).** `php artisan db:seed --class=CategorySeeder --force`
> creates the Leamate category tree, moves products out of the old demo categories and
> deactivates those (no products, prices or images are changed; safe to re-run).
>
> **Upgrading to protected product images (once).** Run `php artisan images:protect` after the
> migration. It moves product display images written to the public disk by earlier versions into
> private storage, so their old `/storage/products/…` URLs stop working and images are served
> only through the watermarked media route. It is safe to re-run. Configure B2B pricing and
> watermarks in Admin → B2B settings (docs/B2B.md).

## Queue worker (required)

Emails, image optimisation and image-provider calls are queued on the `database` connection.
Run a supervised worker, for example with Supervisor:

```ini
[program:atelier-worker]
command=php /var/www/atelier/artisan queue:work --sleep=3 --tries=3 --max-time=3600
user=www-data
autostart=true
autorestart=true
stopwaitsecs=120
redirect_stderr=true
stdout_logfile=/var/www/atelier/storage/logs/worker.log
```

**Failure handling.** Mail jobs retry 3 times (back-off 1 and 5 minutes). If a quote email
ultimately fails, an `email_failed` entry appears on that quote's activity timeline so staff can
re-send. Failed jobs are kept in `failed_jobs`: inspect them with `php artisan queue:failed` and
retry with `php artisan queue:retry all`. Failed jobs older than 30 days are pruned daily.

## Scheduler (required)

```cron
* * * * * cd /var/www/atelier && php artisan schedule:run >> /dev/null 2>&1
```

Scheduled tasks: `quotes:expire` (00:15 daily; marks sent quotations past their validity date as
expired) and `queue:prune-failed`.

## Email (SMTP)

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.com
MAIL_PORT=587
MAIL_USERNAME=...
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS=quotes@yourdomain.com
MAIL_FROM_NAME="Your Brand"
```

Configure SPF, DKIM and DMARC for the sending domain. Set the sales notification address in
Admin → Settings. Send a test quote to yourself before launch.

## Storage

- Local (default): public media in `storage/app/public` (served via the `public/storage` symlink),
  private files (quote attachments, original photos) in `storage/app/private`, which is **not** web
  accessible. Attachments are streamed only after an authorisation check.
- S3: set `MEDIA_DISK_DRIVER=s3` and/or `PRIVATE_DISK_DRIVER=s3` with the `AWS_*` variables (see
  ENV.md). Keep the bucket's `private/` prefix non-public. Existing files are not migrated
  automatically, so copy `storage/app/public` → `s3://bucket/public` and `storage/app/private` →
  `s3://bucket/private` when switching.

## Backups and recovery

Back up both the **database** and the **private storage** (attachments, original photos).

```bash
# nightly database dump (keep at least 14 days, store off-server)
mysqldump --single-transaction --routines --triggers -u backup -p claudestore | gzip > /backups/db-$(date +%F).sql.gz
# files
rsync -a /var/www/atelier/storage/app/ /backups/storage/
```

Recovery:

1. Provision the server as in "First deploy" (skip seeding).
2. `gunzip < db-YYYY-MM-DD.sql.gz | mysql -u root claudestore`
3. Restore `storage/app/` and run `php artisan storage:link`.
4. `php artisan optimize && php artisan queue:restart`.

Test a restore at least once before launch. With S3, enable bucket versioning instead of rsync.

## Logging and monitoring

- Set `LOG_CHANNEL=stack`, `LOG_STACK=daily`, `LOG_LEVEL=warning`. Logs live in `storage/logs`.
- Never log secrets. The application does not log passwords or quote tokens. Two caveats: the
  **log mail driver** writes full emails, including secure links, to the log, so use it in
  development only. Also, while a quotation email waits in the queue, its secure link sits in the
  `jobs` table payload and is deleted once the job runs. Protect database access accordingly.
- An external error tracker (Sentry, Flare, Bugsnag) is not bundled. Install its Laravel package
  and DSN if you want one.
- Health check endpoint: `/up`.

## Security checklist

- [ ] `APP_DEBUG=false`, `APP_ENV=production`, HTTPS enforced, secure cookies
- [ ] Strong admin password; change the seeded admin email; create named staff accounts
- [ ] Least-privilege DB user; backups configured and restore tested
- [ ] SMTP verified; sales notification email set
- [ ] Demo products archived or replaced (they are marked "demo" in Admin → Products)
- [ ] Policies (terms, privacy, shipping, returns) entered in Admin → Settings
- [ ] Queue worker and scheduler running
