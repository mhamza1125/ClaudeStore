# Pricing rules

All prices are calculated on the server by `App\Pricing\PriceResolver`. The browser never computes
or submits a price: the product page re-prices every quantity/variant change with a Livewire
round-trip, the basket re-prices on every render, and quote submission re-prices from the
catalogue. Client-posted price fields are ignored.

## Money

- Stored as **integer minor units (cents)**, base currency **USD**. Other currencies on the quote
  form are informational ("preferred currency").
- Percentages are **basis points** (100 bps = 1%).
- Percent amounts are rounded **half up to the cent**: `round(amount × bps / 10000)` computed as
  `intdiv(amount × bps + 5000, 10000)`. No floating point is used (`app/Support/Money.php`).
- Admin inputs accept plain decimals (`12.50`); anything with more than two decimals is rejected.

## Precedence (first match wins)

For a product, optional variant, quantity and buyer company:

0. **Price on request** products have no price at any level.
1. **Negotiated company price** — `customer_prices` for the company: a row for the **variant**
   first, then a product-level row. Only rows whose `min_qty ≤ quantity` and whose validity window
   (`valid_from`/`valid_until`) includes today; the highest qualifying `min_qty` wins.
2. **Customer-group tier price** — `price_tiers` with the company's `customer_group_id`:
   variant-specific group tiers if the variant has any, otherwise product-level group tiers.
   If the quantity is below the first group threshold, evaluation falls through to level 3.
3. **Standard quantity tiers** — `price_tiers` without a group: **variant tiers replace product
   tiers entirely** when the variant has any; otherwise product tiers.
4. **Base price** — the variant's `price_cents`, then the product's `base_price_cents`.

Levels 1 and 2 apply **only when the buyer's company is wholesale-approved**
(`companies.wholesale_status = approved`). Guests and unapproved accounts get levels 3–4.

**Customer-group discount** (`customer_groups.discount_bps`, optional): applied to level 3 and 4
prices for approved companies in that group. It is *not* stacked on group tiers or negotiated
prices, which are already account-specific.

A tier applies from its `min_qty` up to (next tier's `min_qty` − 1).

### Illustrative example (seed data — edit in Admin → Products)

| Quantity | Unit price |
| --- | --- |
| 1–24 | $18.00 |
| 25–99 | $15.00 |
| 100–249 | $12.50 |
| 250+ | $10.00 |

10 → $18.00 · 50 → $15.00 · 150 → $12.50 · 300 → $10.00 (covered by `tests/Feature/Pricing`).

## Quantity rules

- **MOQ**: variant `moq` overrides product `moq`. Enforced when adding to the basket, when
  editing basket lines and again on submission.
- **Order multiple / case pack**: product `order_multiple`; quantity must be a multiple.
- **Minimum order value** (Settings): checked on submission when every line has a price visible
  to the buyer; otherwise the quotation team handles it.

## Visibility

- `public_pricing = false`: prices are shown only to approved wholesale accounts. Guests see
  "Trade pricing". Hidden prices are never rendered, never used for catalogue sorting/price
  filters, and not written into JSON-LD.
- Negotiated and group prices are shown only to members of that company.
- Indicative prices are stored on quote lines only if the buyer could see them.

## Quotations and orders

Staff set final prices per line in the quote workspace ("Apply catalogue prices" fills empty
lines from the engine for the quote's company). Totals (`App\Pricing\QuoteTotals`):

```
line_total = unit × qty − round(unit × qty × line_discount_bps / 10000)
subtotal   = Σ line_total
discount   = order-level discount (capped at subtotal)
taxable    = subtotal − discount + additional charges (+ shipping if "tax on shipping")
tax        = round(taxable × tax_rate_bps / 10000)
total      = subtotal − discount + shipping + additional + tax
```

Tax rate and shipping default from Admin → Settings (flat rates) and can be overridden per quote.
Each send stores an immutable **version snapshot**; acceptance is tied to a version, and
conversion to a sales order copies the accepted snapshot, so later catalogue edits never change
historical quotes or orders.
