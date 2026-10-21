# Status: implemented, pending, limitations

Last verified: `php artisan test`, 112 tests and 561 assertions, passing on SQLite in-memory and on
MariaDB 10.11. `npm run build` succeeds. A browser walkthrough (Chromium/Playwright, demo data)
covered: tier pricing at 10/50/150/300 units, MOQ/multiple messages, a guest RFQ, admin pricing
and send, the secure link from the mail log, explicit acceptance, conversion to a sales order,
dark-theme persistence, and mobile layout without horizontal overflow. No JS errors were seen.

MySQL 8 itself was **not** available in the build environment. The suite was run on MariaDB 10.11
and SQLite. Run it once on your MySQL 8 server.

## Implemented

**Storefront.** Homepage sections, catalogue (categories/subcategories, search by
name/SKU/code/variant SKU, material/colour/availability/list-price filters, sorting, pagination,
grid/list views, bulk selection with quick quantities), product pages (gallery with view types and
variant images, verified-only specifications, variants, MOQ/multiples, live server-side tier
pricing, next-tier hint, customisation request, favourites, spec-sheet download, related products,
SEO meta/canonical/JSON-LD), policy and company pages, newsletter signup (toggle in Settings).

**Design system.** Semantic tokens in Tailwind v4 `@theme` (the default palette is disabled, so
hard-coded colours don't compile). Light/dark/system themes with a no-flash head script; the
choice is stored in localStorage and on the user record. A toggle sits in the storefront, portal
and admin. Contrast was checked to WCAG AA for the token pairs. Emails and PDFs always use the
light theme.

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

## Needs credentials or external setup

| Item | What's needed |
| --- | --- |
| Production email | SMTP credentials and a verified sending domain (`MAIL_*`) |
| S3 storage | Bucket and keys (`MEDIA_DISK_DRIVER`/`PRIVATE_DISK_DRIVER=s3`, `AWS_*`) |
| Background removal | `IMAGE_PROVIDER=removebg` + `REMOVEBG_API_KEY`. Only tested with faked HTTP |
| AI image generation | No provider bundled. Implement `ImageProvider::generateView` for a chosen service |
| Error monitoring | Choose a service (Sentry/Flare/…) and install its package |
| Fonts | Loaded from Google Fonts (Cormorant Garamond, Inter). Self-host them if required for privacy/GDPR |

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
  between versions, and PDFs always render the current state (which equals the last sent version
  unless there are unsent changes).
- **Two-factor authentication and email verification** are disabled (Fortify supports both; they
  need UI screens).
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
