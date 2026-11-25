# CONFIDENTIAL - do not deploy until the owner has reviewed it

# Running Leamate on Hostinger shared hosting

Owner decision D2 (docs/DECISIONS.md): production is Hostinger **shared** hosting with cron, no
Supervisor, no Node on the host, PHP 8.3+. Every statement marked **(verify on the host)** depends
on the plan and must be checked in hPanel before relying on it. If the host is a VPS instead, use
docs/DEPLOYMENT.md (Supervisor recipe).

## 1. Why cron matters

Without a running queue worker and scheduler the site still serves pages, but:

| Stops working | Because |
| --- | --- |
| Every email (RFQ confirmation, staff alert, **quotation with its secure link**, verification, wholesale, contact) | Mail is queued (`QUEUE_CONNECTION=database`) |
| Product image optimisation and provider jobs | Queued jobs |
| Quote expiry status (acceptance is still blocked by date) | `quotes:expire` is scheduled |
| Image cache pruning, disposable-email list refresh | Scheduled |

Admin → any page shows a warning to admins when: the oldest queued job is older than 5 minutes,
the scheduler heartbeat is older than 5 minutes (or has never been written), or failed jobs exist
(`App\Services\SystemHealth`).

## 2. Cron jobs (hPanel → Advanced → Cron Jobs) (verify on the host)

Replace `/home/USER/public_html` with the real project path and `php` with the PHP 8.3 binary
path hPanel shows (for example `/usr/bin/php8.3` or `/opt/alt/php83/usr/bin/php`).

| Schedule | Command |
| --- | --- |
| every minute (`* * * * *`) | `cd /home/USER/public_html && php artisan schedule:run >> /dev/null 2>&1` |
| every minute (`* * * * *`) | `cd /home/USER/public_html && php artisan queue:work --stop-when-empty --tries=3 --max-time=55 >> /dev/null 2>&1` |

`--max-time=55` ends each worker before the next minute's starts. `config/queue.php`
`retry_after` (180 s) is longer than the longest job timeout (120 s), so a slow job is never handed
to a second worker. The schedule runs `quotes:expire` at 00:15 **Asia/Karachi**.

If the plan limits cron to every 5 minutes or longer (verify on the host), emails are delayed by
that interval and the admin warning may appear briefly; prefer a plan with per-minute cron.

## 3. PHP settings (hPanel → Advanced → PHP Configuration) (verify on the host)

| Setting | Value | Why |
| --- | --- | --- |
| PHP version | 8.3 or newer | `composer.json` requires `^8.3` and resolves packages for 8.3 (docs/DECISIONS.md D10) |
| Extensions | pdo_mysql, mbstring, intl, gd, zip, fileinfo, openssl, curl | Laravel, dompdf, Intervention Image |
| `upload_max_filesize` | 16M | One attachment is up to 10 MB; product originals up to 15 MB |
| `post_max_size` | 64M | An RFQ may carry 5 × 10 MB attachments plus form data |
| `memory_limit` | 256M | dompdf quotation PDFs and image resizing |
| `max_execution_time` | 120 | PDF rendering and image processing in web requests |
| `dns_get_record` | enabled | Email-domain checks. If disabled, addresses are accepted and a warning is logged (it never blocks RFQs) |
| `proc_open` | not required | — |

## 4. Files and storage

- Run `php artisan storage:link` once. Shared hosts sometimes forbid symlinks (verify on the host);
  if so, ask support to enable them for the account.
- Product images are cached as renditions in `storage/app/private/image-cache` and pruned daily;
  watch inode usage on small plans (verify on the host).
- The root `.htaccess` sends every request into `public/` and refuses dot-files. It only works
  while `mod_rewrite` is on (verify on the host); without it the project files are readable.

## 5. Front-end assets (no Node on the host)

`public/build` is not committed. On your own machine, after pulling the release:

```bash
npm ci
npm run build
```

Upload the whole `public/build` folder to the server (File Manager or SFTP), replacing the old one,
**every** deploy. A missing or stale build breaks the layout (docs/DEPLOYMENT.md).

## 6. Deploy steps

```bash
php artisan down --render="errors::503"
git pull
composer install --no-dev --optimize-autoloader
# upload public/build built locally (section 5)
php artisan migrate --force          # additive migrations only
php artisan optimize
php artisan queue:restart
php artisan up
```

Never run `migrate:fresh`, `migrate:refresh`, `migrate:reset`, `migrate:rollback` or `db:wipe`:
the application refuses them, and production data must never be deleted.

## 7. Optional hardening (.env)

| Variable | Default | When to set |
| --- | --- | --- |
| `SECURITY_HSTS_MAX_AGE` | 0 (off) | Only after HTTPS works on every page. Start with `300`; raise to `31536000` after a week without problems |
| `TRUSTED_PROXIES` | empty (trust none) | Only if Hostinger puts a proxy/CDN in front of PHP. Test: temporarily log `request()->ip()` on one request; if it shows a Hostinger/CDN address instead of your own IP, set the proxy addresses the host documents (or `*` only if PHP is reachable exclusively through the proxy) |

A Content-Security-Policy is not sent yet because Livewire and Alpine use inline scripts. Proposed
next step: a `Content-Security-Policy-Report-Only` header to collect violations before enforcing.

## 8. Post-deploy smoke test

1. Open the home page and one product page: layout correct (build uploaded).
2. Admin → dashboard shows **no** health warning (cron running). Right after the first deploy the
   scheduler warning disappears within a minute.
3. Submit a test RFQ with your own email: the confirmation email arrives within ~2 minutes.
4. In the admin, price and send it to yourself: the quotation email with the PDF and secure link
   arrives; the link opens.
5. Check `storage/logs/laravel.log` for new errors.
