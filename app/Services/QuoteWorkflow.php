<?php

namespace App\Services;

use App\Enums\QuoteStatus;
use App\Enums\SalesOrderStatus;
use App\Mail\QuotationSent;
use App\Mail\QuoteResponded;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\QuoteActivity;
use App\Models\QuoteItem;
use App\Models\SalesOrder;
use App\Models\User;
use App\Pricing\PriceResolver;
use App\Pricing\QuoteTotals;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * All state changes of a quotation after submission. Controllers and Livewire
 * components call this service; it enforces the status rules and audit trail.
 */
class QuoteWorkflow
{
    public function __construct(private PriceResolver $resolver, private ReferenceGenerator $references) {}

    public function log(Quote $quote, ?User $user, string $type, ?string $body = null, array $meta = [], bool $internal = true): QuoteActivity
    {
        return QuoteActivity::create([
            'quote_id' => $quote->id, 'user_id' => $user?->id, 'type' => $type, 'body' => $body,
            'meta' => $meta ?: null, 'is_internal' => $internal,
        ]);
    }

    public function setStatus(Quote $quote, QuoteStatus $status, ?User $user, ?string $note = null): void
    {
        if ($quote->status === $status) {
            return;
        }
        $from = $quote->status;
        $quote->update(['status' => $status]);
        $this->log($quote, $user, 'status', $note ?? "Status changed from {$from->label()} to {$status->label()}.", ['from' => $from->value, 'to' => $status->value]);
    }

    public function startReview(Quote $quote, User $user): void
    {
        if ($quote->status === QuoteStatus::Pending) {
            $this->setStatus($quote, QuoteStatus::UnderReview, $user);
        }
    }

    public function assign(Quote $quote, ?User $assignee, User $actor): void
    {
        if ($assignee && ! $assignee->isStaff()) {
            throw ValidationException::withMessages(['assignee' => 'Quotes can only be assigned to staff.']);
        }
        $quote->update(['assigned_to' => $assignee?->id]);
        $this->log($quote, $actor, 'assigned', $assignee ? "Assigned to {$assignee->name}." : 'Unassigned.');
    }

    public function addNote(Quote $quote, User $user, string $body): void
    {
        $this->log($quote, $user, 'note', trim($body));
    }

    /**
     * Save negotiated line prices, quantities, discounts and commercial terms.
     *
     * @param  array<int, array{quantity:int, unit_price_cents:?int, discount_bps:int}>  $lines  keyed by quote item id
     * @param  array{discount_cents:int, shipping_cents:int, additional_charges_cents:int, additional_charges_label:?string, tax_rate_bps:int, valid_until:?string, payment_terms:?string, delivery_terms:?string, customer_notes:?string, po_reference?:?string}  $terms
     */
    public function saveCommercials(Quote $quote, array $lines, array $terms, User $user): void
    {
        $this->assertEditable($quote);

        DB::transaction(function () use ($quote, $lines, $terms, $user) {
            $priceChanges = [];
            foreach ($quote->items as $item) {
                if (! array_key_exists($item->id, $lines)) {
                    continue;
                }
                $line = $lines[$item->id];
                $new = [
                    'quantity' => max(1, (int) $line['quantity']),
                    'unit_price_cents' => $line['unit_price_cents'] === null ? null : max(0, (int) $line['unit_price_cents']),
                    'discount_bps' => min(10000, max(0, (int) $line['discount_bps'])),
                ];
                foreach ($new as $key => $value) {
                    if ($item->{$key} !== $value) {
                        $priceChanges[] = ['sku' => $item->sku, 'field' => $key, 'from' => $item->{$key}, 'to' => $value];
                    }
                }
                $new['line_total_cents'] = $new['unit_price_cents'] === null ? null
                    : QuoteTotals::lineTotal($new['unit_price_cents'], $new['quantity'], $new['discount_bps']);
                $item->update($new);
            }

            $before = $quote->only(['discount_cents', 'shipping_cents', 'additional_charges_cents', 'tax_rate_bps', 'valid_until', 'payment_terms', 'delivery_terms']);
            $quote->fill([
                'discount_cents' => max(0, (int) $terms['discount_cents']),
                'shipping_cents' => max(0, (int) $terms['shipping_cents']),
                'additional_charges_cents' => max(0, (int) $terms['additional_charges_cents']),
                'additional_charges_label' => $terms['additional_charges_label'] ?? null,
                'tax_rate_bps' => min(10000, max(0, (int) $terms['tax_rate_bps'])),
                'valid_until' => $terms['valid_until'] ?: null,
                'payment_terms' => $terms['payment_terms'] ?? null,
                'delivery_terms' => $terms['delivery_terms'] ?? null,
                'customer_notes' => $terms['customer_notes'] ?? null,
                'po_reference' => $terms['po_reference'] ?? $quote->po_reference,
            ]);
            $termChanges = AuditLogger::diff($quote, array_keys($before));
            $this->recalculate($quote);

            if ($quote->status === QuoteStatus::AwaitingResponse || $quote->status === QuoteStatus::Expired) {
                $quote->has_unsent_changes = true;
            }
            $quote->save();

            if (in_array($quote->status, [QuoteStatus::Pending, QuoteStatus::UnderReview], true) && $this->fullyPriced($quote)) {
                $this->setStatus($quote, QuoteStatus::Quoted, $user);
            }
            if ($quote->status === QuoteStatus::Expired && $quote->valid_until && ! $quote->isExpired()) {
                $this->setStatus($quote, QuoteStatus::Quoted, $user, 'Validity extended to '.$quote->valid_until->toDateString().'. Re-send required.');
            }

            if ($priceChanges || $termChanges) {
                $this->log($quote, $user, 'price_change', 'Pricing or terms updated.', ['lines' => $priceChanges, 'terms' => $termChanges]);
            }
        });
    }

    public function addLine(Quote $quote, Product $product, ?ProductVariant $variant, int $quantity, User $user): QuoteItem
    {
        $this->assertEditable($quote);
        if ($variant && $variant->product_id !== $product->id) {
            throw ValidationException::withMessages(['newLine' => 'That option does not belong to the product.']);
        }
        $price = $this->resolver->resolve($product, $variant, max(1, $quantity), $quote->company?->loadMissing('customerGroup'));
        $item = $quote->items()->create([
            'product_id' => $product->id, 'variant_id' => $variant?->id,
            'sku' => $variant?->sku ?? $product->sku, 'product_name' => $product->name, 'variant_name' => $variant?->name,
            'product_snapshot' => ['material' => $product->material, 'dimensions' => $product->dimensions, 'color' => $variant?->color, 'size' => $variant?->size],
            'quantity' => max(1, $quantity),
            'unit_price_cents' => $price->unitCents,
            'line_total_cents' => $price->unitCents === null ? null : $price->unitCents * max(1, $quantity),
            'sort_order' => (int) $quote->items()->max('sort_order') + 1,
        ]);
        $this->afterLineChange($quote, $user, "Added line {$item->sku} × {$item->quantity}.");

        return $item;
    }

    public function removeLine(Quote $quote, QuoteItem $item, User $user): void
    {
        $this->assertEditable($quote);
        abort_unless($item->quote_id === $quote->id, 404);
        $item->delete();
        $this->afterLineChange($quote, $user, "Removed line {$item->sku}.");
    }

    /** Fill missing unit prices from the pricing engine for the quote's company. */
    public function applySuggestedPrices(Quote $quote, User $user): int
    {
        $this->assertEditable($quote);
        $company = $quote->company?->loadMissing('customerGroup');
        $count = 0;
        foreach ($quote->items()->with(['product.priceTiers', 'variant'])->get() as $item) {
            if ($item->unit_price_cents !== null || ! $item->product) {
                continue;
            }
            $price = $this->resolver->resolve($item->product, $item->variant, $item->quantity, $company);
            if ($price->unitCents !== null) {
                $item->update([
                    'unit_price_cents' => $price->unitCents,
                    'line_total_cents' => QuoteTotals::lineTotal($price->unitCents, $item->quantity, $item->discount_bps),
                ]);
                $count++;
            }
        }
        if ($count) {
            $this->afterLineChange($quote, $user, "Applied catalogue pricing to {$count} line(s).");
        }

        return $count;
    }

    /**
     * Issue the quotation: snapshot a new version, mint a fresh secure link and email it with the PDF.
     *
     * @return string the customer link (shown once to staff, emailed to the buyer)
     */
    public function send(Quote $quote, User $user): string
    {
        $quote->refresh()->load('items');
        if (! in_array($quote->status, [QuoteStatus::Quoted, QuoteStatus::AwaitingResponse, QuoteStatus::UnderReview, QuoteStatus::Expired], true)) {
            throw ValidationException::withMessages(['send' => "A quote with status “{$quote->status->label()}” cannot be sent."]);
        }
        if ($quote->items->isEmpty()) {
            throw ValidationException::withMessages(['send' => 'Add at least one priced line before sending.']);
        }
        if (! $this->fullyPriced($quote)) {
            throw ValidationException::withMessages(['send' => 'Every line needs a unit price before the quotation can be sent.']);
        }
        if (! $quote->valid_until || $quote->isExpired()) {
            throw ValidationException::withMessages(['send' => 'Set a validity date in the future before sending.']);
        }

        $token = Str::random(48);
        DB::transaction(function () use ($quote, $user, $token) {
            $version = $quote->version + 1;
            $quote->versions()->create(['version' => $version, 'snapshot' => $this->snapshot($quote), 'created_by' => $user->id]);
            $quote->update([
                'version' => $version,
                'access_token_hash' => hash('sha256', $token),
                'sent_at' => now(),
                'has_unsent_changes' => false,
            ]);
            $this->setStatus($quote, QuoteStatus::AwaitingResponse, $user);
            $this->log($quote, $user, 'sent', "Quotation version {$version} sent to {$quote->email}.", ['version' => $version], internal: false);
        });

        $link = route('quotes.public.show', [$quote, $token]);
        try {
            Mail::to($quote->email, $quote->contact_name)->queue(new QuotationSent($quote, $link));
        } catch (\Throwable $e) {
            Log::error('Quotation email dispatch failed', ['quote' => $quote->reference, 'error' => $e->getMessage()]);
            $this->log($quote, $user, 'email_failed', 'Quotation email could not be queued: '.$e->getMessage());
        }

        return $link;
    }

    public function tokenMatches(Quote $quote, string $token): bool
    {
        return $quote->access_token_hash !== null && hash_equals($quote->access_token_hash, hash('sha256', $token));
    }

    /** Customer acceptance or rejection (via secure link or the customer portal). */
    public function respond(Quote $quote, bool $accept, int $version, ?string $name, ?string $reason, ?User $user): void
    {
        if ($quote->status === QuoteStatus::AwaitingResponse && $quote->isExpired()) {
            // Recorded outside the response transaction so the status change persists.
            $this->setStatus($quote, QuoteStatus::Expired, null, 'Quotation expired before the customer responded.');
        }
        if ($quote->status === QuoteStatus::Expired) {
            throw ValidationException::withMessages(['response' => 'This quotation has expired. Please contact us to have it revalidated or extended.']);
        }

        DB::transaction(function () use ($quote, $accept, $version, $name, $reason, $user) {
            $quote = Quote::query()->whereKey($quote->id)->lockForUpdate()->firstOrFail();
            if (! $quote->canBeAnsweredByCustomer()) {
                throw ValidationException::withMessages(['response' => 'This quotation can no longer be accepted or rejected online.']);
            }
            if ($version !== $quote->version) {
                throw ValidationException::withMessages(['response' => 'This quotation has been revised. Please review the latest version.']);
            }

            $quote->fill([
                'responded_at' => now(),
                'accepted_by_name' => $accept ? $name : null,
                'rejection_reason' => $accept ? null : $reason,
            ])->save();
            $this->setStatus($quote, $accept ? QuoteStatus::Accepted : QuoteStatus::Rejected, $user,
                $accept ? "Accepted by {$name} (version {$version})." : 'Rejected by customer'.($reason ? ": {$reason}" : '.'));
        });

        $quote->refresh();
        $recipients = collect([setting('sales.notification_email'), $quote->assignee?->email])->filter()->unique();
        if ($recipients->isNotEmpty()) {
            try {
                Mail::to($recipients->all())->queue(new QuoteResponded($quote));
            } catch (\Throwable $e) {
                Log::error('Quote response notification failed', ['quote' => $quote->reference, 'error' => $e->getMessage()]);
            }
        }
    }

    /** Re-open a rejected or expired quote for revision. */
    public function reopen(Quote $quote, User $user): void
    {
        if (! in_array($quote->status, [QuoteStatus::Rejected, QuoteStatus::Expired], true)) {
            throw ValidationException::withMessages(['status' => 'Only rejected or expired quotes can be reopened.']);
        }
        $quote->update(['has_unsent_changes' => true]);
        $this->setStatus($quote, QuoteStatus::UnderReview, $user, 'Reopened for revision.');
    }

    /** Convert an accepted quote into a sales order, preserving agreed prices and terms. */
    public function convertToOrder(Quote $quote, User $user): SalesOrder
    {
        return DB::transaction(function () use ($quote, $user) {
            $quote = Quote::query()->whereKey($quote->id)->lockForUpdate()->with('items')->firstOrFail();
            if ($quote->status !== QuoteStatus::Accepted) {
                throw ValidationException::withMessages(['convert' => 'Only accepted quotations can be converted to an order.']);
            }
            // Use the accepted version snapshot so later edits can never alter the order.
            $snapshot = $quote->versions()->where('version', $quote->version)->value('snapshot');
            $snapshot = is_string($snapshot) ? json_decode($snapshot, true) : $snapshot;

            $order = SalesOrder::create([
                'reference' => $this->references->next('SO'),
                'quote_id' => $quote->id,
                'company_id' => $quote->company_id,
                'user_id' => $quote->user_id,
                'created_by' => $user->id,
                'status' => SalesOrderStatus::Confirmed,
                ...collect($quote->only(['company_name', 'contact_name', 'email', 'phone', 'country', 'delivery_destination', 'po_reference', 'currency']))->all(),
                ...collect($snapshot['totals'])->only(['subtotal_cents', 'discount_cents', 'shipping_cents', 'additional_charges_cents', 'additional_charges_label', 'tax_rate_bps', 'tax_cents', 'total_cents'])->all(),
                'payment_terms' => $snapshot['payment_terms'] ?? null,
                'delivery_terms' => $snapshot['delivery_terms'] ?? null,
            ]);
            foreach ($snapshot['items'] as $line) {
                $order->items()->create([
                    'product_id' => $line['product_id'], 'variant_id' => $line['variant_id'],
                    'sku' => $line['sku'], 'product_name' => $line['product_name'], 'variant_name' => $line['variant_name'],
                    'quantity' => $line['quantity'], 'unit_price_cents' => $line['unit_price_cents'],
                    'discount_bps' => $line['discount_bps'], 'line_total_cents' => $line['line_total_cents'],
                    'customization' => $line['customization'],
                ]);
            }
            $this->setStatus($quote, QuoteStatus::Converted, $user, "Converted to sales order {$order->reference}.");
            AuditLogger::log('quote.converted', $quote, "Quote {$quote->reference} converted to order {$order->reference}.");

            return $order;
        });
    }

    /** Scheduled: mark sent quotations past their validity date as expired. */
    public function expireOverdue(): int
    {
        $count = 0;
        Quote::query()->where('status', QuoteStatus::AwaitingResponse)->whereDate('valid_until', '<', today())
            ->each(function (Quote $quote) use (&$count) {
                $this->setStatus($quote, QuoteStatus::Expired, null, 'Quotation validity date passed.');
                $count++;
            });

        return $count;
    }

    public function recalculate(Quote $quote): void
    {
        $items = $quote->items()->get();
        $totals = QuoteTotals::calculate(
            $items->whereNotNull('unit_price_cents')->map(fn (QuoteItem $i) => [
                'unit_cents' => $i->unit_price_cents, 'quantity' => $i->quantity, 'discount_bps' => $i->discount_bps,
            ]),
            (int) $quote->discount_cents, (int) $quote->shipping_cents, (int) $quote->additional_charges_cents,
            (int) $quote->tax_rate_bps, (bool) setting('tax.on_shipping', true),
        );
        $quote->fill([
            'subtotal_cents' => $totals->subtotalCents,
            'discount_cents' => $totals->discountCents,
            'tax_cents' => $totals->taxCents,
            'total_cents' => $totals->totalCents,
        ]);
    }

    public function fullyPriced(Quote $quote): bool
    {
        $items = $quote->items()->get();

        return $items->isNotEmpty() && $items->every(fn (QuoteItem $i) => $i->unit_price_cents !== null);
    }

    private function afterLineChange(Quote $quote, User $user, string $message): void
    {
        $this->recalculate($quote);
        if (in_array($quote->status, [QuoteStatus::AwaitingResponse, QuoteStatus::Expired], true)) {
            $quote->has_unsent_changes = true;
        }
        $quote->save();
        $this->log($quote, $user, 'price_change', $message);
    }

    private function assertEditable(Quote $quote): void
    {
        if (! $quote->status->isEditable()) {
            throw ValidationException::withMessages(['status' => "A quote with status “{$quote->status->label()}” is locked and cannot be edited."]);
        }
    }

    private function snapshot(Quote $quote): array
    {
        return [
            'items' => $quote->items->map(fn (QuoteItem $i) => $i->only([
                'id', 'product_id', 'variant_id', 'sku', 'product_name', 'variant_name', 'quantity', 'customization',
                'unit_price_cents', 'discount_bps', 'line_total_cents',
            ]))->values()->all(),
            'totals' => $quote->only(['subtotal_cents', 'discount_cents', 'shipping_cents', 'additional_charges_cents', 'additional_charges_label', 'tax_rate_bps', 'tax_cents', 'total_cents', 'currency']),
            'valid_until' => $quote->valid_until?->toDateString(),
            'payment_terms' => $quote->payment_terms,
            'delivery_terms' => $quote->delivery_terms,
            'customer_notes' => $quote->customer_notes,
        ];
    }
}
