<?php

namespace App\Quotes;

use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\QuoteVersion;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * What a quotation document shows: the customer page, portal page, PDF and email.
 *
 * Customer-facing output is built only from a sent version's snapshot (fromVersion), so a
 * customer never sees staff's unsent edits (audit F-02). Staff output may be built from live
 * data (live), and is then labelled as a draft.
 *
 * Snapshots written before schema 2 lack the customer block and the resolved terms text. For
 * those, only the submission identity is taken from the quote row: company, contact, email,
 * phone, country, delivery destination and target date are written once by
 * QuoteSubmissionService and never edited afterwards. Editable values (PO reference, VAT
 * number, settings-based terms) are omitted rather than read live.
 */
final class QuotationDocument
{
    /**
     * @param  array{company_name:?string, contact_name:?string, email:?string, phone:?string, country:?string, delivery_destination:?string, target_delivery_date:?Carbon, po_reference:?string, vat_number:?string}  $customer
     * @param  Collection<int, object>  $items
     * @param  array{subtotal_cents:int, discount_cents:int, shipping_cents:int, additional_charges_cents:int, additional_charges_label:?string, tax_rate_bps:int, tax_cents:int, total_cents:int}  $totals
     */
    public function __construct(
        public readonly string $reference,
        public readonly int $version,
        public readonly bool $isDraft,
        public readonly ?string $draftLabel,
        public readonly ?Carbon $issuedAt,
        public readonly ?Carbon $validUntil,
        public readonly string $currency,
        public readonly array $customer,
        public readonly Collection $items,
        public readonly array $totals,
        public readonly ?string $paymentTerms,
        public readonly ?string $deliveryTerms,
        public readonly ?string $customerNotes,
        public readonly ?string $acceptanceInstructions,
        public readonly string $taxLabel,
    ) {}

    /** The document exactly as it was sent in $version. Never reads live commercial data. */
    public static function fromVersion(Quote $quote, QuoteVersion $version): self
    {
        $s = $version->snapshot ?? [];
        $totals = $s['totals'] ?? [];
        $customer = $s['customer'] ?? null;

        return new self(
            reference: $quote->reference,
            version: (int) $version->version,
            isDraft: false,
            draftLabel: null,
            issuedAt: isset($s['issued_at']) ? Carbon::parse($s['issued_at']) : $version->created_at,
            validUntil: isset($s['valid_until']) ? Carbon::parse($s['valid_until']) : null,
            currency: (string) ($totals['currency'] ?? $quote->currency),
            customer: $customer !== null ? self::customerFrom($customer) : self::submissionIdentity($quote),
            items: collect($s['items'] ?? [])->map(fn (array $i) => (object) [
                'sku' => $i['sku'] ?? '', 'product_name' => $i['product_name'] ?? '', 'variant_name' => $i['variant_name'] ?? null,
                'quantity' => (int) ($i['quantity'] ?? 0), 'customization' => $i['customization'] ?? null,
                'unit_price_cents' => isset($i['unit_price_cents']) ? (int) $i['unit_price_cents'] : null,
                'discount_bps' => (int) ($i['discount_bps'] ?? 0),
                'line_total_cents' => isset($i['line_total_cents']) ? (int) $i['line_total_cents'] : null,
            ])->values(),
            totals: self::totalsFrom($totals),
            // Schema 2 stores the text that was printed (custom terms or the defaults at send time).
            paymentTerms: $s['terms_text']['payment'] ?? ($s['payment_terms'] ?? null),
            deliveryTerms: $s['terms_text']['delivery'] ?? ($s['delivery_terms'] ?? null),
            customerNotes: $s['customer_notes'] ?? null,
            acceptanceInstructions: $s['terms_text']['acceptance'] ?? null,
            taxLabel: (string) ($s['tax_label'] ?? 'Tax'),
        );
    }

    /** Staff view of the current, possibly unsent, state. */
    public static function live(Quote $quote): self
    {
        $quote->loadMissing(['items', 'company']);
        $label = match (true) {
            $quote->has_unsent_changes => 'Draft - unsent changes',
            $quote->version === 0 => 'Draft - not yet sent',
            default => null,
        };

        return new self(
            reference: $quote->reference,
            version: max(1, (int) $quote->version),
            isDraft: $label !== null,
            draftLabel: $label,
            issuedAt: $quote->sent_at,
            validUntil: $quote->valid_until,
            currency: (string) $quote->currency,
            customer: [...self::submissionIdentity($quote), 'po_reference' => $quote->po_reference, 'vat_number' => $quote->company?->vat_number],
            items: $quote->items->map(fn (QuoteItem $i) => (object) $i->only([
                'sku', 'product_name', 'variant_name', 'quantity', 'customization', 'unit_price_cents', 'discount_bps', 'line_total_cents',
            ]))->values(),
            totals: self::totalsFrom($quote->only(['subtotal_cents', 'discount_cents', 'shipping_cents', 'additional_charges_cents', 'additional_charges_label', 'tax_rate_bps', 'tax_cents', 'total_cents'])),
            paymentTerms: $quote->payment_terms ?: setting('quote.payment_terms'),
            deliveryTerms: $quote->delivery_terms ?: setting('quote.delivery_terms'),
            customerNotes: $quote->customer_notes,
            acceptanceInstructions: setting('quote.acceptance_instructions'),
            taxLabel: (string) setting('tax.label', 'Tax'),
        );
    }

    /** The extra snapshot fields written from schema 2 on (QuoteWorkflow::snapshot). */
    public static function snapshotExtras(Quote $quote): array
    {
        return [
            'schema' => 2,
            'issued_at' => now()->toIso8601String(),
            'customer' => [
                ...$quote->only(['company_name', 'contact_name', 'email', 'phone', 'country', 'delivery_destination', 'po_reference']),
                'target_delivery_date' => $quote->target_delivery_date?->toDateString(),
                'vat_number' => $quote->company?->vat_number,
            ],
            'tax_label' => (string) setting('tax.label', 'Tax'),
            'terms_text' => [
                'payment' => $quote->payment_terms ?: setting('quote.payment_terms'),
                'delivery' => $quote->delivery_terms ?: setting('quote.delivery_terms'),
                'acceptance' => setting('quote.acceptance_instructions'),
            ],
        ];
    }

    private static function submissionIdentity(Quote $quote): array
    {
        return [
            ...$quote->only(['company_name', 'contact_name', 'email', 'phone', 'country', 'delivery_destination']),
            'target_delivery_date' => $quote->target_delivery_date,
            'po_reference' => null,
            'vat_number' => null,
        ];
    }

    private static function customerFrom(array $c): array
    {
        return [
            'company_name' => $c['company_name'] ?? null, 'contact_name' => $c['contact_name'] ?? null,
            'email' => $c['email'] ?? null, 'phone' => $c['phone'] ?? null, 'country' => $c['country'] ?? null,
            'delivery_destination' => $c['delivery_destination'] ?? null,
            'target_delivery_date' => isset($c['target_delivery_date']) ? Carbon::parse($c['target_delivery_date']) : null,
            'po_reference' => $c['po_reference'] ?? null, 'vat_number' => $c['vat_number'] ?? null,
        ];
    }

    private static function totalsFrom(array $t): array
    {
        return [
            'subtotal_cents' => (int) ($t['subtotal_cents'] ?? 0), 'discount_cents' => (int) ($t['discount_cents'] ?? 0),
            'shipping_cents' => (int) ($t['shipping_cents'] ?? 0), 'additional_charges_cents' => (int) ($t['additional_charges_cents'] ?? 0),
            'additional_charges_label' => $t['additional_charges_label'] ?? null, 'tax_rate_bps' => (int) ($t['tax_rate_bps'] ?? 0),
            'tax_cents' => (int) ($t['tax_cents'] ?? 0), 'total_cents' => (int) ($t['total_cents'] ?? 0),
        ];
    }
}
