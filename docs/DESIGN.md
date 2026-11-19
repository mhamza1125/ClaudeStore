# Leamate design system — "Workshop Modern"

One light theme for the storefront, customer portal and admin. Everything lives in
`resources/css/app.css` (tokens + component classes) and `resources/views/components`.

## 1. Direction

Leamate is a Sialkot leather manufacturer selling wholesale and private label to retailers,
distributors and brands. The site has to feel **crafted** (it is a workshop, not a marketplace)
and **trustworthy** (B2B buyers compare MOQs, price breaks and lead times). The direction is
editorial and warm, with one deep accent that makes every action obvious.

- **Thesis:** cream hide, saddle-brown leather, deep walnut for every action, the logo tan for identity.
- **Signature:** the saddle stitch. Dashed "stitched" rules, an inset stitched frame on key
  surfaces (hero image, footer CTA, auth panel, empty states) and a walnut stitched underline on the
  active nav item. Use it sparingly: at most one stitched element per viewport.
- **Squint test:** the primary action is the strongest-contrast element on any screen (walnut on cream, tan on espresso).

## 2. Colour (60 · 30 · 10) — an all-leather palette

| Role | Token | Hex | Use |
| --- | --- | --- | --- |
| 60 · Canvas | `bg` | `#faf7f2` | Page background |
| | `surface` | `#ffffff` | Cards, inputs, menus |
| | `elevated` | `#f2ece2` | Cream panels, table heads, image placeholders |
| 30 · Saddle | `text` / `band` | `#221710` | Text (16:1 on canvas), dark bands, footer |
| | `muted` | `#6b5d52` | Secondary text (5.9:1) |
| | `clay` | `#8a6a4f` | Muted saddle: eyebrows, numerals, icons (4.6:1) |
| | `hide` | `#b69878` | Muted tan details on dark bands (the buttons own the bright tan) |
| 10 · Walnut | `primary` | `#684333` | Primary CTA fill, white text (9.2:1) |
| | `primary-hover` | `#573729` | Primary hover |
| | `accent` | `#684333` | Links, focus rings, active states (8.6:1 on canvas) |
| | `accent-soft` | `#efe4dc` | Selected rows, active chips, badges |
| | `tan` / `tan-hover` | `#d99a63` / `#c4834a` | The logo "Mate" tan: buttons on dark bands, with espresso text |
| | `thread` | `#d99a63` | Tan thread: stitches and highlights on dark bands |

The reasoning: the palette stays entirely within leather tones, so the site reads as one material.
**Tan is the brand identity colour; walnut is the action colour.** Deep walnut buttons with white
text carry the hierarchy on cream. On espresso bands a walnut button would vanish, so `.band
.btn-primary` switches to the logo tan with espresso text. Small decorative details use the muted
saddle `clay`. (Blue, patina teal, cognac and tan-with-black-text buttons were tried first.)

Status colours (`success`, `warning`, `danger` + `-soft`) are only for states, never for brand.

## 3. Typography

Self-hosted variable fonts (`resources/fonts/web`, SIL OFL; preloaded in `partials/head`):

- **Fraunces** (display, optical sizing): `h1–h3`, `.h-hero` (44→84px), `.h-section` (34→56px),
  `.h-product`, `.h-card`, prices and big numbers. Weight 420–500, tight negative tracking. Use
  *italic* in `text-hide`/`text-clay` for one emphasised phrase in a headline at most. Numerals
  are forced to lining figures (Fraunces defaults to old-style, where 0 looks like O).
- **Instrument Sans** (body and UI): 15px base, 600 for buttons and labels.
- `.eyebrow` (12px, caps, 0.16em, clay) introduces sections; `.eyebrow-stitch` adds the stitch lead-in.

## 4. Components

| Class | Notes |
| --- | --- |
| `.btn` + `-primary` / `-secondary` / `-dark` / `-light` / `-outline-light` / `-ghost` / `-danger` | Pill, min 44px tall (`-sm` 36px, `-lg` 52px). One primary per view. A trailing arrow icon nudges on hover. `-icon` for square icon buttons. |
| `.card`, `.panel`, `.panel-cream`, `.card-hover` | 16px radius, 1px border, no shadow at rest; `card-hover` lifts on hover. |
| `.input` | 44px, 10px radius, walnut focus ring; selects get a custom chevron. |
| `.badge-*`, `.chip`, `.dot` | Pills. Availability on product cards is a coloured dot + text, not a badge. |
| `.alert` + `badge-*` | Inline notices (flash messages, warnings). |
| `.tile`, `.tile-label`, `.tile-arrow` | Category/raw-leather tiles. The label always sits on a solid card, so names stay readable on any photograph. |
| `.product-card` | Container-query card (`@container`); hides SKU/MOQ when narrow. Whole card is one link. |
| `.stitch`, `.stitch-v`, `.stitch-frame` | The signature. `stitch-frame` takes `--stitch-color`, `--stitch-inset`, `--stitch-radius`. |
| `.band`, `.grain` | Espresso band with a fine leather grain; links and stitches switch to `thread`. |
| `.stat`, `.stat-value`, `.stat-icon` | Admin and portal metrics. |
| `.admin-nav-link`, `.admin-nav-group` | Grouped admin sidebar with a walnut active marker. |
| `<x-breadcrumbs :items>` | Visible trail + BreadcrumbList JSON-LD. |
| `<x-empty>` | Dashed "stitched patch" empty state with one next step. |

Spacing: sections use `.section-y` (64→112px) or `.section-y-sm`; content width `.container-page`
(1280px). Radii: 6 / 10 / 16 / 24px. Shadows are soft and warm-tinted (`shadow-sm/md/lg`), plus
`shadow-cta` (walnut glow) on primary hover only.

## 5. Motion

Purposeful and sparse: the hero crossfade with a slow zoom and staggered copy; a gentle `.reveal`
fade-up on scroll; image zoom on card hover; arrow nudges on CTAs. All of it is disabled under
`prefers-reduced-motion`. No looping decorative animation.

## 6. Page patterns

- **Home:** hero (headline + one walnut CTA; image in a stitched frame over a cream block; static
  trade-facts row) → ways to work with us → bento collections → trade favourites carousel → raw
  leather → private-label band with stitched timeline → "About + the Leamate standard" spec
  sheet → testimonials (only if real ones exist). Each section uses a different layout on purpose.
- **Footer:** site-wide quote CTA (hidden on focused flows: sign-in, quote form, portal).
- **Catalogue:** light header with breadcrumbs, collection chips, then a sticky filter card.
- **Product:** gallery + purchase panel (walnut "Add to Quote"); description + spec-sheet card.
- **Admin:** same tokens and type, denser layout, no marketing elements. Page titles in Fraunces,
  everything else in sans.

## 7. Imagery

The shipped images are **demo illustrations** (clearly labelled "Demo image"), drawn by
`database/seeders/Support/DemoImage.php` in one consistent studio style. `php artisan
demo:refresh-artwork` redraws existing demo files in place. It writes files only and never
touches database rows or uploaded images.

They are placeholders. For launch, replace them with real photography to this brief:

| Use | Ratio / size | Brief |
| --- | --- | --- |
| Product (front/back/side/detail) | 4:5, ≥ 1600×2000 | Seamless warm off-white backdrop (close to `#f2eadd`), soft key light from top-left, gentle contact shadow, product centred and filling ~65% of the height. Same set-up for every product. |
| Product lifestyle | 4:5 | Workshop table (walnut or vegetable-tanned hide), one prop at most; kraft paper or a cream card as the one prop. |
| Category tiles | ~6:5, ≥ 2000×1650 | Two or three products from the category in a still life, centred in the upper two-thirds (a label card covers the bottom). |
| Homepage banners | 4:3, ≥ 2400×1800 | Product on a plinth, or hands at work in the Sialkot workshop. Keep the left third calm. |

Upload JPG/PNG/WebP in admin; the pipeline converts product photos to watermarked WebP
renditions (320/800/1600px). No stock photography of other brands' goods, and no AI images
presented as Leamate products.

## 8. SEO

- Unique titles and meta descriptions per page; canonical URLs; Open Graph (product image and
  `og:type=product` on product pages; `summary_large_image` when there is an image).
- Structured data: Organization + WebSite with SearchAction (home), Product (product pages),
  BreadcrumbList (catalogue, product and content pages).
- `/sitemap.xml` (published products, active categories, content and policy pages; cached for 1
  hour) and `/robots.txt` (generated; points to the sitemap and keeps `/admin`, `/account`, `/q/`
  and transactional URLs out). The rules are identical in every environment, so a wrong
  `APP_ENV` can never de-index the site.
- `noindex, follow` on search results, filtered or sorted catalogue views, auth pages, the portal,
  secure quotation links and the quote confirmation.
- One `<h1>` per page; sections use `<h2>`/`<h3>` in order.

## 9. Blog: recommendation

**Worth doing, but only with a publishing commitment.** B2B buyers research before they request
quotes, and the site currently has no content that answers their questions. A small "Trade
guides" section (4–6 articles to start, then 1–2 a month) could rank for high-intent searches:

- Leather types explained: full-grain vs top-grain vs genuine, and what to specify
- How private-label leather goods work: MOQs, sampling, lead times
- Embossing vs debossing vs foil: choosing a logo method
- Importing leather goods from Pakistan: shipping, Incoterms and documents
- Caring for vegetable-tanned leather (shareable with retail customers)

Each guide should link to the relevant category and to the quote form, with Article +
BreadcrumbList structured data. It isn't built yet, because empty or thin articles would hurt
more than help. When the team is ready to write, the editorial layout should reuse
`.h-section`, `.prose-store` and the product card for "products in this guide".
