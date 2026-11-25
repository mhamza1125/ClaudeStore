# Status: implemented, pending, limitations

Last verified **2026-11-25**: `php artisan test` on SQLite in-memory: **352 tests, 1885 assertions,
all passing** (0 failed, 0 skipped, 0 incomplete). This is the single place for the test count.
New migrations were rehearsed on an empty MariaDB scratch database (`leamate_scratch`); the local
server is MariaDB (XAMPP), not MySQL 8. Front-end build, browser walkthrough and MySQL 8 were not
re-run in this round.

An earlier round (before the redesign) also ran a Chromium walkthrough of pricing, a guest RFQ,
sending, the secure link, acceptance and conversion; it predates the current single light theme.

## Fixed on 2026-11-25 (audit docs/AUDIT_2026-11-25.md)

- **F-02** Customers see only the sent version: secure-link page, portal page, PDF and the
  quotation email (which now carries the version) render from the version snapshot; staff PDF is
  live and labelled "Draft - unsent changes".
- **F-04** Variants are deactivated, never deleted; negotiated prices and variant tiers survive;
  tiers are updated in place (editor and CSV); inactive variants are ignored everywhere.
- **F-12** Quote workspace: row lock plus `lock_version`, so a save from a stale screen is refused
  without writing; saves after acceptance are refused.
- **F-13** Quote validity, the expiry job (00:15) and negotiated-price windows use the business
  calendar, Asia/Karachi; stored timestamps stay UTC.
- **F-06 / F-16 / F-33 / F-08 / F-18** Admin health warning (queue, scheduler heartbeat, failed
  jobs); `retry_after` 180 s; email-domain checks fail open on DNS errors; security headers
  (no-referrer and no-store on secure links; HSTS opt-in); trusted proxies from `.env`.
  Hosting recipe in docs/HOSTINGER.md.
- **F-05 / F-09 / F-10 / F-11 / F-23** Crawlable catalogue pagination with per-page canonicals;
  schema.org availability mapping; `/quote` noindex; 301s for renamed slugs, 301/410 for archived
  products; sitemap without demo products or empty categories, refreshed on change; custom 404.
- **F-17 / F-20 / F-25 / F-28** Generic password-reset answer; correct tier ladder next to
  negotiated prices; temporary staff password never kept in component state; first reference
  of a year race-safe. (F-26 needed no change: Fortify already lowercases emails.)
- **D3** Admin → B2B settings warns while anyone who registers can see trade-only prices.
- **F-07** Dependencies patched (D10): laravel/framework 12.74.0, laravel/fortify 1.44.0,
  symfony/* 7.4.22–7.4.23, web-auth/webauthn-lib 5.3.17 (29 minor/patch updates). `composer audit`:
  0 advisories (was 18 in 8 packages). The suite passes on PHP 8.2.12 (local XAMPP).

## Open after 2026-11-25

- npm: 1 high advisory (`follow-redirects`, build-time dev dependency), not changed.
- Questions Q11, Q13–Q15 in docs/AUDIT_QUESTIONS.md (FK restrict, Composer pin, blank CSV tier cell,
  old snapshots, address edits by members).
- Owner actions in docs/OWNER_ACTIONS.md (token rotation, cron, backups, demo data, seeded admin
  email, trade-price setting, "genuine leather" text, privacy review, Search Console).
- Other times still shown/compared in UTC (not changed, findings only): admin date filters on the
  quote list, the workspace's default validity date, the reference-number year at the new year,
  the RFQ "target delivery date after today" rule, and displayed timestamps in admin, portal and
  emails.

## Accepted risks

- **Seeded demo accounts** (`admin@atelierhide.test`, `sales@atelierhide.test`): kept for now;
  rename or deactivate them before wider exposure (docs/OWNER_ACTIONS.md item 5).
- **Trade-only prices with instant access** (D3): kept by decision; an admin warning explains it.

## Implemented

**Storefront.** Homepage sections, catalogue (categories/subcategories, search by
name/SKU/code/variant SKU, material/colour/availability/list-price filters, sorting, pagination,
grid/list views, bulk selection with quick quantities), product pages (gallery with view types and
variant images, verified-only specifications, variants, MOQ/multiples, live server-side tier
pricing, next-tier hint, customisation request, favourites, spec-sheet download, related products,
SEO meta/canonical/JSON-LD), policy and company pages, newsletter signup (toggle in Settings).

**Design system.** "Workshop Modern" (docs/DESIGN.md): one light theme for the storefront, portal
and admin, with semantic tokens in Tailwind v4 `@theme` (the default palette is disabled, so
hard-coded colours don't compile). 60-30-10 palette (canvas / espresso + saddle / deep walnut actions, the logo tan for identity and for buttons on dark bands),
self-hosted Fraunces + Instrument Sans, and a saddle-stitch signature motif. Contrast was checked
to WCAG AA for the token pairs. Emails and PDFs use the same palette. Theme switching was removed;
the unused `users.theme` column and old `theme.*` settings rows are left in place (never deleted).

**Pricing.** The full precedence chain, group discounts, MOQ, order multiples, minimum order
value, price-on-request, trade-only visibility, integer money. See PRICING.md.

**Quotes.** Server-side basket (guest and user, merged on login). RFQ form with honeypot, timing
check, rate limits and private attachments (PDF/JPG/PNG/WebP, MIME-checked). Sequential references,
snapshots, confirmation page and emails. Admin workspace with filters (status, date, company,
country, sales rep), assignment, internal notes, negotiated prices, line and order discounts,
shipping/tax/additional charges, validity and terms, add/remove lines, catalogue-price fill,
PDF, versioned send with a rotating hashed secure link, activity trail with price-change diffs,
reopen, expiry (scheduled), customer accept/reject (explicit confirmation, version check,
expiry/revision guards), conversion to sales orders from the accepted snapshot, order status
management.

**Portal.** Registration with a company profile, company details, billing/shipping addresses,
wholesale access request with staff approval and emails, account pricing page, quote history,
PDF and accept/reject, orders, reorder at current prices, favourites, team list.

**Admin.** Dashboard. Products (create/edit/duplicate/publish/archive, variants, standard/variant/
group tiers, specifications, availability, visibility flags, SEO, images, spec sheet).
Categories (two levels). Customers (approval, group, sales rep, payment terms, negotiated prices,
members). Customer groups. Settings (brand, logo, commercial defaults, terms, policies,
testimonials, newsletter). Staff and roles (super-admin only). CSV import/export (dry run,
all-or-nothing, formula escaping). Audit log.

**Security.** CSRF; policies and permission middleware; super-admin `Gate::before`; rate limits
on login (5/min), RFQ, quote responses, newsletter and sensitive portal actions; deactivated users
cannot sign in. Private attachments and originals; secure links stored as SHA-256 hashes; Blade
escaping throughout; Eloquent/parameterised queries; LIKE wildcards escaped in searches.
Audit log for product, price, settings, customer, staff and order changes.

**Engagement.** Homepage hero banner with admin-managed slides (Admin → Homepage slides: image or
product image with focal point, eyebrow/title/subtitle, two CTAs, scheduling, ordering), crossfade
with progress-timed autoplay, pause, arrows, dots, swipe and reduced-motion support. Featured
product carousel (scroll-snap, autoplay that pauses on hover/focus/touch, arrows). Live header
search suggestions (`/search/suggest`, same matching as the catalogue). One light theme throughout
(the earlier White / Mid / Dark switcher was removed in the redesign). Informational pages at root URLs (`/about`, `/custom-manufacturing`) with 301
redirects from `/pages/…`. Configurable floating WhatsApp button (bottom-left) and optional AI
chatbot (bottom-right) with Cloudflare Workers AI as the default provider (Anthropic optional),
public-data-only business context and an honest offline mode (docs/CHATBOT.md).

**B2B price access and image protection.** Admin → B2B settings: B2B pricing on/off, guest
prices, registration requirement, four access methods (instant, verify email, approval, both),
guest inquiries, company and personalised watermarks. Admin → Customer accounts: approve, reject,
resend or mark verification, per-customer price override, suspend. Enforced server-side in every
price channel. Product images are private and served as signed, watermarked renditions, with a
zoom view. See docs/B2B.md and docs/IMAGES.md (`tests/Feature/B2b`, `tests/Feature/Images`).

**Leamate pre-launch.** Branding (logo in header/footer/admin, favicons, "Leamate | Leather
Goods & Accessories" title, Open Graph tags), official contact details (ribbon, footer, Contact
page, emails, WhatsApp), Leamate category tree (`CategorySeeder`, idempotent), Contact Us page
with stored messages, email alert and Admin → Messages, single centred image watermark
(embedded or CSS overlay), prices hidden from guests by default, footer spacing fixed.

## Needs credentials or external setup

| Item | What's needed |
| --- | --- |
| AI chatbot | `CLOUDFLARE_ACCOUNT_ID` + `CLOUDFLARE_API_TOKEN` (Workers AI permission) in `.env`, `php artisan config:cache`, `php artisan assistant:check`, then enable it in Admin → Settings → Chatbot. Not tested against the live Cloudflare API yet (no credentials; outbound access blocked in the build environment) |
| Production email | SMTP credentials and a verified sending domain (`MAIL_*`) |
| S3 storage | Bucket and keys (`MEDIA_DISK_DRIVER`/`PRIVATE_DISK_DRIVER=s3`, `AWS_*`) |
| Background removal | `IMAGE_PROVIDER=removebg` + `REMOVEBG_API_KEY`. Only tested with faked HTTP |
| AI image generation | No provider bundled. Implement `ImageProvider::generateView` for a chosen service |
| Error monitoring | Choose a service (Sentry/Flare/…) and install its package |

## Business decisions / content still required

- Real products, verified specifications, prices and tiers; real photography (demo SVGs are placeholders)
- Brand name, logo, contact details and social links (Admin → Settings)
- Policies: terms of trade, privacy, shipping, returns (pages show a "not yet published" notice until then)
- Tax rate/treatment, shipping defaults, minimum order value, quote validity, payment and delivery terms
- Customer group structure and any group discounts
- Genuine testimonials (the section is hidden until supplied)
- Homepage hero and "About" copy (currently placeholder text that says so)

## Known limitations

- **Currency:** quotes and orders are issued in USD only. "Preferred currency" is informational.
- **Tax:** a single flat rate per quote (default from Settings). There are no tax tables by
  country, and no exemption handling beyond setting the quote's rate to 0.
- **Shipping:** a flat default per quote, edited manually. No carrier rates.
- **Payments:** none by design. Pro forma invoices and payment tracking are not implemented.
- **Team accounts:** company owners can't invite members themselves. Staff add registered users
  to a company (Admin → Customers). Company members can view company quotes; only the submitter
  or an owner can accept or reject.
- **Stock:** availability status and an informational stock count. No reservation or decrement
  when orders are created.
- **Category tree:** two levels (category → subcategory).
- **Product duplication** copies variants and tiers but not images.
- **Quote versions:** snapshots are stored and listed with totals. There is no visual diff
  between versions. Customer PDFs, pages and emails render the sent version's snapshot; the staff
  PDF shows the live state, labelled "Draft - unsent changes" when it differs.
- **Two-factor authentication** is disabled (Fortify supports it; it needs UI screens). Email
  verification is enabled; whether it gates prices is a B2B setting (docs/B2B.md).
- **Newsletter:** subscribers are stored only. There is no export UI or email-provider sync, and
  no double opt-in.
- **Search** uses SQL `LIKE`. It's fine for a few thousand products; add Scout/Meilisearch for
  large catalogues.
- **Bulk "add selected"** in the list view requires choosing an option for products that have
  variants.
- **Demo images** are SVG illustrations, not photographs.
- The seeded demo passwords are random per seed and printed only outside production.

## Git history note

Phase 2 (foundation) was committed on its own and verified in isolation. Phases 3–5 were committed
together because the admin routes and navigation depend on admin screens built in the same pass.
Phase 6 (tests, fixes, end-to-end check) and Phase 7 (docs) are separate commits.
