# Browser deployment (Hostinger shared hosting)

Open `https://leamate.com/deploy.php`, enter the deploy token, press **Deploy now**. The page
pulls the latest code from `claude/dreamy-pasteur-s1ejbf` and prints a short ✓/✗ step list.

| File | Role |
| --- | --- |
| `public/deploy.php` | The endpoint. GET shows the form; only POST with a valid token deploys |
| `deploy/Deployer.php` | Deployment logic (framework-free, so it still works if a deploy breaks Laravel) |
| `deploy/set-token.php` | Creates/rotates the token (SSH only; refuses to run from the web) |

Private data lives **outside the web root** in `~/domains/leamate.com/.deploy/`
(override with the `DEPLOY_PRIVATE_DIR` environment variable):
`token.hash`, optional `config.php`, `attempts.json`, `deploy.lock`, `logs/`.

## What a deployment does

1. Refuses to run if another deployment holds the lock (HTTP 409).
2. Checks the server is on the configured branch and that **no tracked file was modified on the
   server** — if one was, it stops and changes nothing.
3. `git pull --ff-only origin claude/dreamy-pasteur-s1ejbf` — fast-forward only: never a merge
   commit, reset, clean or force. If histories diverged it fails and leaves everything as it was.
4. `composer install --no-dev --optimize-autoloader` — only when `composer.lock` changed or
   `vendor/` is missing.
5. `npm ci && npm run build` — only when front-end files changed or `public/build` is missing,
   **and** npm exists on the server. Hostinger shared hosting usually has no npm: you then get a
   warning, and must run `npm run build` locally and upload `public/build/` (it is not in Git).
6. `php artisan optimize:clear`
7. *Only if ticked:* `php artisan migrate --force`
8. *Only if ticked:* `php artisan db:seed --class=RolesAndPermissionsSeeder --force`
9. `php artisan optimize`, `storage:link` (if missing), `queue:restart`
10. Reports pending migrations as a warning when you did not tick "Run migrations".

**Never touched:** `.env`, `storage/` contents, uploaded files, `vendor/` (except via composer),
`public/storage` and anything else Git does not track.

### Why seeding is limited to roles & permissions

`php artisan db:seed` (or `migrate --seed`) is **not** safe to repeat on this project: `StaffSeeder`
resets the admin password on every run, and the demo seeders delete and recreate demo price
tiers, variants and images. Run the full seeder once, by hand, on a fresh database only.
`RolesAndPermissionsSeeder` is idempotent and is the only seeder the page offers.

## Security model

- POST only (other methods → 405), HTTPS only (→ 403), token in the POST body, never the URL.
- The token is 64 random hex characters; only its `password_hash()` is stored, mode 0600, outside
  the web root. Not in `.env`, not in Git.
- 5 failed attempts from one IP lock that IP out for 15 minutes (HTTP 429).
- Without `token.hash` the endpoint is disabled (HTTP 503).
- Responses show step names and ✓/✗ only — no command output, paths or configuration.
  Full output is written to `.deploy/logs/` (0600, last 30 kept); credentials embedded in URLs are
  redacted.
- Commands run without a shell (argument arrays), so no request data reaches a command line.
- `no-store`, `X-Frame-Options: DENY`, strict CSP, `noindex`, `no-referrer`.

## One-time setup (over SSH)

### 0. Prerequisites

- The project directory must already be a Git checkout of `claude/dreamy-pasteur-s1ejbf` with
  your server files reconciled (the initial sync). Check: `git status` shows the branch and
  "nothing to commit" for tracked files.
- **Protect the project root.** Your document root is `public_html`, which is also the Laravel
  root, so without protection `/.env` could be downloadable. Either set the domain's document
  root to `public_html/public` in hPanel (if your plan allows), or — after checking that
  `public_html/.htaccess` does not already exist or merging by hand — copy
  `deploy/htaccess-root.example` to `public_html/.htaccess`. Then verify:

  ```bash
  curl -sI https://leamate.com/.env | head -1          # must be 403 or 404
  curl -sI https://leamate.com/.git/config | head -1   # must be 403 or 404
  ```

### 1. GitHub access for `git pull` (private repository)

Use a read-only deploy key; no token in the remote URL:

```bash
ssh-keygen -t ed25519 -f ~/.ssh/claudestore_deploy -N "" -C "leamate deploy"
cat ~/.ssh/claudestore_deploy.pub
```

GitHub → repository → Settings → Deploy keys → Add key → paste it, leave "Allow write access"
**off**. Then:

```bash
printf 'Host github.com\n  IdentityFile ~/.ssh/claudestore_deploy\n  IdentitiesOnly yes\n' >> ~/.ssh/config
chmod 600 ~/.ssh/config
ssh -T git@github.com            # answer "yes" once to trust GitHub's host key
cd ~/domains/leamate.com/public_html
git remote set-url origin git@github.com:mhamza1125/ClaudeStore.git
git pull --ff-only origin claude/dreamy-pasteur-s1ejbf   # must work without a password prompt
```

### 2. Find the CLI tools and write the config

```bash
which php composer npm git; php -v | head -1
```

If `php -v` is not the version your site uses (Hostinger often has several), use the full path,
e.g. `/opt/alt/php83/usr/bin/php`. Create `~/domains/leamate.com/.deploy/config.php` (only the
keys you need to change; defaults are in `Deployer::DEFAULTS`):

```php
<?php return [
    'php' => '/usr/bin/php',
    'composer' => '/usr/local/bin/composer',
    'path' => '/usr/local/bin:/usr/bin:/bin',
    // 'allowed_ips' => ['203.0.113.7'],   // optional, only if your IP is fixed
];
```

```bash
chmod 600 ~/domains/leamate.com/.deploy/config.php
```

### 3. Create the token

```bash
cd ~/domains/leamate.com/public_html
php deploy/set-token.php
```

It prints the token **once**. Save it in your password manager. Run the command again at any time
to rotate it; the old token stops working immediately. Never put it in `.env`, Git, chat or a URL.

## Deploying

1. Open `https://leamate.com/deploy.php` (bookmark the **https** address).
2. Paste the token. Tick "Run database migrations" only when the release has migrations
   (the page warns you when some are pending). Tick "Sync roles & permissions" only when
   permissions changed.
3. Press **Deploy now** and wait (usually 10–60 s; longer when composer runs).
   You see "Deployment succeeded" or "Deployment failed" with the failed step.
   Details: `ls -t ~/domains/leamate.com/.deploy/logs | head -1`.

If the page shows "proc_open is disabled", this hosting plan does not allow PHP to run commands
from the web; deploy over SSH with the same commands instead.

If the browser times out on a long run, the deployment keeps going on the server
(`ignore_user_abort`). Reload the page after a minute: a second attempt returns "already running"
until it finishes, then check the log.

## Rolling back

The page never rolls back automatically. A failed step after the pull leaves the new code in
place and reports the previous commit. Preferred fix: revert the bad commit on GitHub
(`git revert <sha>` locally, push) and deploy again — this keeps fast-forward pulls working.

## Disabling

`rm ~/domains/leamate.com/.deploy/token.hash` turns the endpoint off (HTTP 503) until you run
`set-token.php` again.
