# CONFIDENTIAL - until the owner has reviewed it

# Owner actions (outside the code)

Things only the owner can do. Tick them off here.

| # | Action | Where | Why |
|---|---|---|---|
| 1 | **Rotate the Cloudflare API token** (create a new one limited to Workers AI, put it only in the server's `.env`, revoke the old one). Then delete or move `Notes.txt` out of the project folder | Cloudflare dashboard → My Profile → API Tokens; server `.env`; then `php artisan config:cache` and `php artisan assistant:check` | The token sat in plain text outside `.env` and was pasted into an AI chat (D1). `.gitignore` now keeps `Notes.txt` out of git, but the file still exists locally |
| 2 | **Set up cron** for the scheduler and the queue worker, and check the PHP settings | hPanel → Advanced → Cron Jobs / PHP Configuration; recipe in docs/HOSTINGER.md | Without it no email (including quotations) is sent. Admin pages show a warning until it runs |
| 3 | **Backups with one test restore** (database + `storage/app`) | Hostinger backups or your own; procedure in docs/DEPLOYMENT.md "Backups and recovery" | Production holds real customer data and there is no rollback (D9) |
| 4 | **Check production for demo products** and archive or replace them | Admin → Products (filter by "Demo product" badge) | Demo listings must not be indexed; the sitemap now skips them, but they still show on the site |
| 5 | **Rename the seeded admin email** `admin@atelierhide.test` (and remove the demo sales rep if present) | Admin → Staff; profile page | It is a known test address; use named staff accounts |
| 6 | **Decide the B2B trade-price setting** (D3): with instant access, anyone who registers sees trade-only prices | Admin → B2B settings (a warning explains the current effect) | Trade prices visible to competitors with a free account |
| 7 | **Edit the "genuine leather" wording** in the Leather Bags category description unless it is verified | Admin → Categories → Leather Bags → Description | Do not state unverified product claims (the seeder only creates it once; editing it in admin is permanent) |
| 8 | **Have privacy and erasure handling reviewed** by a qualified person (retention, erasure requests vs. the no-delete rule) | — | D5 |
| 9 | **Submit the sitemap** `https://<your-domain>/sitemap.xml` and check indexing | Google Search Console (and Bing Webmaster Tools) | Pagination and redirects changed; confirm Google sees them |
| 10 | Answer the open questions Q11–Q15 | docs/AUDIT_QUESTIONS.md | Dependency patch (Q12) and FK guarantee (Q11) wait on them |
