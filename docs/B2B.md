# B2B price access

Admin → **B2B settings** decides who can see catalogue prices and how customers gain access.
Everything is stored in the `settings` table and takes effect immediately. Admin →
**Customer accounts** is where staff approve, verify, override or suspend individual customers.

## Settings

| Setting | Key | Default |
| --- | --- | --- |
| Enable B2B pricing | `b2b.enabled` | **on** |
| Require customer registration to view prices | `b2b.require_registration` | on |
| Show prices to guest visitors (when registration is not required) | `b2b.guest_prices` | on |
| Require email verification to view prices | `b2b.require_email_verification` | off |
| Require manual admin approval | `b2b.require_approval` | off |
| Allow guests to submit product inquiries | `b2b.guest_inquiries` | on |
| Company watermark / text / opacity | `images.watermark_enabled`, `images.watermark_text`, `images.watermark_opacity` | on / business name / 22% |
| Watermark mode | `images.watermark_mode` | `embedded` (in the served copy) or `overlay` (CSS on the page) |
| Personalised watermark / identifier | `images.watermark_personalized`, `images.watermark_identifier` | off / email |

B2B pricing is **on by default**: visitors must register to see prices. (Before, with nothing
saved in Admin → B2B settings, it defaulted to off, so products marked "public pricing", which
is most of the demo catalogue, showed their list price to every visitor.) With B2B pricing
switched **off**, the original rules apply (docs/PRICING.md): public products show list prices
to everyone, trade-only products to approved wholesale companies.

## Access methods (registration required)

The two checkboxes combine into four methods:

| Method | Verification | Approval | Prices appear |
| --- | --- | --- | --- |
| Register and access | – | – | right after registration |
| Register and verify email | ✓ | – | after the emailed link is clicked |
| Register and wait for approval | – | ✓ | after staff approve the account |
| Register, verify and wait for approval | ✓ | ✓ | when both are done |

- Approval is never needed unless "Require manual admin approval" is ticked. Accounts registered
  while it is off are approved automatically; accounts that existed before this feature were
  approved by the migration.
- Verification emails are sent on registration and when the email address changes. Links are
  signed and expire after `auth.verification.expire` minutes (60). Customers can resend from the
  price box, their dashboard or their profile; staff can resend or mark an address verified.
- When B2B pricing is on and registration is required, a customer with access also sees prices of
  products marked "trade only". Group and negotiated prices still require an approved wholesale
  company, as before.
- Guests can always browse every product and build a quote list. With guest inquiries off, they
  must sign in to submit it (the list is kept and merged on sign-in).
- While B2B pricing is on, each registration emails the sales notification address (a lead), with
  a link to approve it when approval is required.

## Always applied

These apply whether or not B2B pricing is on:

- **Suspended** accounts (`is_active = false`) cannot sign in, are signed out on their next
  request, and see no prices.
- Per-customer **override** (`users.price_access`): *Never show prices* hides all prices; *Always
  show prices* skips verification and approval.
- Staff always see prices.

## Enforcement

`App\Pricing\PriceAccess` produces a `PriceAccessDecision` for the viewer, and `PriceResolver`
applies it. Every storefront path passes the decision: product page (Livewire), catalogue cards
and list view, price sort and filter (ignored without access, so order can't leak prices),
search suggestions JSON, quote list, indicative prices stored on quote requests, account pricing
page, product JSON-LD (anonymous view) and the chatbot context (anonymous view). Hidden prices are
never rendered, never put in a Livewire snapshot or JSON response, and never stored as
"indicative" on quotes.

Covered by `tests/Feature/B2b`.
