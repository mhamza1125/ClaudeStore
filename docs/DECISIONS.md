# CONFIDENTIAL - until the owner has reviewed it

# Decisions

Settled answers, so they are not asked again. Newest first. Source of each: the owner's fix
brief of 2026-11-25 (answers to docs/AUDIT_QUESTIONS.md Q1–Q10). Open questions stay in
docs/AUDIT_QUESTIONS.md until answered, then move here.

| ID | Decision | Consequence in the code / docs |
|---|---|---|
| D10 | **Composer resolves for PHP 8.3** (answer to Q12, 2026-11-25). Production PHP is set to 8.3+ in hPanel; local XAMPP stays on PHP 8.2 | `composer.json`: `require.php ^8.3`, `config.platform.php 8.3.0` (every `composer update` resolves for production, never for the local CLI), `config.platform-check false` (no runtime version guard, so local 8.2 still boots). Keep the local suite green on 8.2; if a package ever needs 8.3-only syntax, upgrade XAMPP |
| D1 | The Cloudflare token found in `Notes.txt` is live. **The owner rotates it.** The repo only stops the file from being committed | `.gitignore` ignores `Notes.txt`, `Notes.md`, `*.local.md`, `docs/AUDIT*.md`. See OWNER_ACTIONS.md |
| D2 | Production is Hostinger **shared** hosting: cron available, no Supervisor, no Node on the host, PHP 8.3+ | docs/HOSTINGER.md (cron recipe, PHP settings, local build); admin health warning (`SystemHealth`). If any part is false, the hosting docs change |
| D3 | **Do not change who sees trade-only prices.** Document the real behaviour and warn admins | Behaviour per setting: docs/B2B.md (matrix, tested by `tests/Feature/B2b/PriceVisibilityMatrixTest.php`). Warning on Admin → B2B settings when instant access is combined with trade-only products. Stored settings untouched |
| D4 | Variants are **deactivated, never deleted**. Negotiated prices are never cascade-deleted. Other deletions stay as they are for now (proposal below) | `ProductVariant` refuses `delete()`; editor "Deactivate"; inactive variants ignored by storefront, search, basket, staff quote lines and negotiated-price entry; tiers updated in place (`PriceTierSync`) |
| D5 | No code for data erasure. **Erasure handling needs review by a qualified person** (privacy law, retention, the no-delete rule) | Nothing implemented. Tracked in OWNER_ACTIONS.md |
| D6 | RFQ attribution, analytics and reports are **not** part of the fix task | Separate later task |
| D7 | The business time zone is **Asia/Karachi** for quote validity and expiry. `app.timezone` stays **UTC** (stored timestamps would shift) | `store.business_timezone` / `BUSINESS_TIMEZONE`; `App\Support\BusinessTime`; "valid until D" = through 23:59:59 on D in Karachi; `quotes:expire` at 00:15 Karachi; negotiated-price windows on business dates |
| D8 | English and USD only; no hreflang work | — |
| D9 | Backups with a tested restore, and checking production for demo data / the seeded admin email, are owner actions outside the code | OWNER_ACTIONS.md |
| — | **No-delete rule** (standing): migrations, seeders and scheduled jobs never delete or overwrite records; migrations are additive; `migrate:fresh/refresh/reset/rollback` and `db:wipe` are refused | `DB::prohibitDestructiveCommands`, `tests/Feature/Security/DataProtectionTest.php`; written out in CLAUDE.md |

## Rules confirmed against official documentation (2026-11-25)

| Rule | Source |
|---|---|
| Paginated pages link with crawlable `<a href>`; each page has its own canonical (not page 1); same titles are allowed | developers.google.com/search/docs/specialty/ecommerce/pagination-and-incremental-page-loading |
| Keep a page out of search with `noindex`, not a robots.txt Disallow (a blocked page's noindex is never seen) | developers.google.com/search/docs/crawling-indexing/block-indexing |
| Product snippets need one of `offers`, `review`, `aggregateRating`: a Product with none is not eligible | developers.google.com/search/docs/appearance/structured-data/product-snippet |
| ItemAvailability values include InStock, LimitedAvailability, MadeToOrder, OutOfStock | schema.org/ItemAvailability |
| 404 and 410 are treated the same; a 301 is a strong signal for the target | developers.google.com/search/docs/crawling-indexing/http-network-errors |

## Proposal: other deletions (D4 asks to list, not change)

Recommended handling per deletion found in the audit (§10.4). None implemented.

| Deletion | Where | Recommended |
|---|---|---|
| Price tier removed by the user | `PriceTierSync` (editor, CSV) | Keep (an explicit user action, audited with before/after) |
| Negotiated price removed | Admin → Customer → Negotiated prices (`CompanyShow::deletePrice`) | Soft delete or `valid_until = yesterday` instead of delete |
| Quote line removed before sending | `QuoteWorkflow::removeLine` | Keep (draft edit, logged; sent versions keep their snapshot) |
| Product image (record + private original file) | `ImageStorage::delete` | Mark rejected/archived; keep the original file |
| Category, customer group, hero slide | Admin managers (only empty categories/groups) | Deactivate flags instead of delete |
| Contact message | Admin → Messages | Archive flag |
| Customer address | Portal → Company (any member) | Soft delete; owner-only edits (open question Q15) |
| Favourites, basket items, guest basket after merge/submit | Portal, basket | Keep (transient data) |
| Staff role change | `syncRoles` | Keep (audited) |
| `images:protect` old public copies | one-off console command | Keep (file move after copy) |
