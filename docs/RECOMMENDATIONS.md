# Recommendations (for approval — not implemented)

Prepared after the homepage banner, themes, live search, widget, Cloudflare chatbot, page-URL
and product-carousel work. Nothing in this document has been built. Priorities: **P1** = do next,
largest effect on enquiries; **P2** = next cycle; **P3** = when content or data exists to support it.

## 1. Recommended additional pages

All are informational pages and fit the existing root-URL scheme (`PageController::CONTENT_PAGES`
plus a Blade view, or settings-backed content like the policies).

| Priority | URL | Purpose & audience | Content / sections | Benefit |
| --- | --- | --- | --- | --- |
| P1 | `/contact` | Buyers who want a person, not a form. All visitors | Email, phone, WhatsApp, address/map, office hours and time zone, a short enquiry form (rate-limited, same spam protection as RFQ), response-time promise, links to quote and wholesale | There is currently no contact page; contact details live only in the footer. Highest-intent page after the quote basket |
| P1 | `/wholesale` | Retailers and resellers deciding whether to open a trade account | How trade pricing works (tiers, MOQ, order multiples), who qualifies, the approval process, payment terms, lead times, sampling, "Open a trade account" CTA | Turns the existing portal/approval feature into a sales page; answers the questions the chatbot gets most |
| P1 | `/faq` | All buyers; also feeds the chatbot's "Additional knowledge" | Grouped Q&A: ordering & MOQ, pricing & quotes, customisation, materials & care, shipping & duties, returns/claims, payment. FAQPage structured data | Fewer repetitive enquiries; better search snippets; one source of truth for the chatbot |
| P2 | `/how-to-order` | First-time B2B buyers | 5-step visual process (browse → add to quote → receive quote → accept → production & delivery), timings per step, what a quote contains, sample PDF | Reduces uncertainty about the RFQ model ("no checkout?") |
| P2 | `/materials` | Buyers comparing quality; brand/sourcing teams | Leather types and grades, tanning, hardware, linings, colour range, care, swatch request CTA | Supports premium positioning with verifiable detail; reusable on product pages |
| P2 | `/samples` | Buyers before a large order | What sample options exist, cost and credit against first order, lead time, request form or quote shortcut | Removes a common blocker for 1,000+ unit orders |
| P3 | `/case-studies` (or `/work`) | Brands evaluating private label | 3–6 real projects: brief, constraints, solution, photos, quantities, quote from client | Strongest trust signal for custom work — **only with real, approved projects** |
| P3 | `/sustainability` | Corporate and EU buyers with sourcing policies | Sourcing, certifications (only those actually held), waste/offcut use, packaging | Answers procurement questionnaires; only worth publishing with substantiated facts |
| P3 | `/corporate-gifts` | HR/marketing buyers ordering branded gifts | Popular gift products, personalisation options, timelines for seasonal deadlines, MOQ | Separate, high-value audience with different search terms |

## 2. Prioritised improvement audit

### P1 — highest impact, low to moderate effort

1. **Replace demo content before launch.** Products, prices, images, testimonials and slides are
   placeholders. Real photography (consistent background, 4:3 and 16:10 crops, a lifestyle shot
   per hero slide) will matter more than any layout change.
2. **Contact, wholesale and FAQ pages** (above), linked from the main navigation and footer.
3. **Responsive images.** No `srcset`/`sizes` is generated; cards and the banner download one size
   for every device. Generate 2–3 widths in `ImageStorage` (already resizes) and emit `srcset`.
   Add `fetchpriority="high"` to the first hero image only. Expected: noticeably faster mobile LCP.
4. **SEO basics.** Add `sitemap.xml` (products, categories, pages), Open Graph/Twitter tags
   (title, description, image) on product, category and home pages, and `Organization` JSON-LD.
   Products already have JSON-LD; categories and pages have none.
5. **Quote CTA consistency.** Product cards say "View & quote", the header "Quote", the banner
   uses admin-defined labels. Pick one verb pair ("Request a quote" / "Add to quote") and use it
   everywhere. After "Add to quote", confirm with a short toast that links to the basket.
6. **Chatbot: content and guardrails.** Fill Admin → Settings → Chatbot → Additional knowledge with
   the FAQ content. Test 20–30 realistic questions on the chosen Workers AI model, especially
   prices, discounts and lead times, before enabling publicly (Llama-family models follow system
   prompts less strictly than larger models).

### P2 — next cycle

7. **Search.** Add category matches and "popular searches" to the suggestion dropdown; log
   zero-result terms (privacy-safe, no user IDs) so the catalogue can use the words buyers type.
   On mobile the search sits inside the menu — consider a search icon in the top bar.
8. **Product discovery.** Category landing copy and filters above the fold on mobile; a
   "Similar products" row on product pages; "Recently viewed" (client-side only).
9. **Trust signals.** Real client logos (with permission), manufacturing capacity, years trading,
   response-time promise near CTAs, and visible payment/shipping terms on the quote page.
10. **Accessibility pass.** Automated axe run on all templates, screen-reader test of the banner
    and search combobox (both built to ARIA patterns but not tested with NVDA/VoiceOver here),
    and a focus-order review of the admin.
11. **Admin usability.** Image cropping preview on the slide editor (focal point is currently a
    preset), bulk publish/unpublish in products, saved filters in the quote list, and a dashboard
    tile for quotes awaiting a reply beyond N hours.
12. **Performance hygiene.** HTTP caching headers for `/storage` images, `php artisan optimize`
    and OPcache in production (documented), a CDN in front of images if traffic grows.

### P3 — later / needs data or decisions

13. **Analytics and funnels** (privacy-friendly, e.g. Plausible) for search → product → quote
    conversion; required before deciding on further UX changes.
14. **Multi-currency display** for international buyers (quote currency remains authoritative).
15. **Chatbot streaming responses** and handoff to WhatsApp/email with the conversation attached
    (with visitor consent).
16. **Case studies and sustainability pages** once real material exists.

### Known residual issues from this round

- Live Cloudflare Workers AI responses are **untested**: no credentials, and outbound access was
  blocked in the build environment. Run `php artisan assistant:check` after configuring.
- The requested model ID `@cf/meta/llama-3.3-70b` is likely not a valid Workers AI ID; the default
  is `@cf/meta/llama-3.3-70b-instruct-fp8-fast`. Confirm in the Cloudflare model catalogue.
- Browser testing used Chromium (Playwright) at phone/tablet/desktop widths; Safari/iOS and
  Firefox were not tested, so `100dvh`, safe-area insets and scroll-snap behaviour on iOS should be
  checked on a device.
- `public/build` is not committed — every deploy must run `npm run build` (this caused the
  original widget-position bug).
