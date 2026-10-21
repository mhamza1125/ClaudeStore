<?php

namespace App\Livewire\Admin;

use App\Enums\QuoteStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Quote;
use App\Models\QuoteItem;
use App\Models\User;
use App\Pricing\QuoteTotals;
use App\Services\QuoteWorkflow;
use App\Support\Money;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;

class QuoteWorkspace extends Component
{
    use AuthorizesRequests;

    #[Locked]
    public int $quoteId;

    /** itemId => [quantity, unit_price (decimal string), discount (percent string)] */
    public array $lines = [];

    public array $terms = [];

    public string $note = '';

    public string $assignee = '';

    public string $newSku = '';

    public $newQty = 1;

    public ?string $sentLink = null;

    public function mount(Quote $quote): void
    {
        $this->authorize('view', $quote);
        $this->quoteId = $quote->id;
        $this->assignee = (string) ($quote->assigned_to ?? '');
        $this->fillForm($this->quote());
    }

    private function fillForm(Quote $quote): void
    {
        $quote->load('items');
        $fresh = $quote->version === 0 && in_array($quote->status, [QuoteStatus::Pending, QuoteStatus::UnderReview], true) && $quote->valid_until === null;

        $this->lines = $quote->items->mapWithKeys(fn (QuoteItem $i) => [$i->id => [
            'quantity' => $i->quantity,
            'unit_price' => Money::toDecimalString($i->unit_price_cents),
            'discount' => Money::bpsToPercentString((int) $i->discount_bps),
        ]])->all();

        // New quotes start from the store-wide flat rates in Settings; all are overridable per quote.
        $this->terms = [
            'discount' => Money::toDecimalString($quote->discount_cents),
            'shipping' => Money::toDecimalString($fresh ? (int) setting('shipping.flat_cents', 0) : $quote->shipping_cents),
            'additional' => Money::toDecimalString($quote->additional_charges_cents),
            'additional_label' => (string) $quote->additional_charges_label,
            'tax_rate' => Money::bpsToPercentString($fresh ? (int) setting('tax.rate_bps', 0) : $quote->tax_rate_bps),
            'valid_until' => $quote->valid_until?->toDateString() ?? ($fresh ? now()->addDays((int) setting('quote.validity_days', 30))->toDateString() : ''),
            'payment_terms' => (string) ($quote->payment_terms ?? ($fresh ? setting('quote.payment_terms') : '')),
            'delivery_terms' => (string) ($quote->delivery_terms ?? ($fresh ? setting('quote.delivery_terms') : '')),
            'customer_notes' => (string) $quote->customer_notes,
            'po_reference' => (string) $quote->po_reference,
        ];
    }

    public function quote(): Quote
    {
        return Quote::query()->findOrFail($this->quoteId);
    }

    public function startReview(QuoteWorkflow $workflow): void
    {
        $quote = $this->quote();
        $this->authorize('update', $quote);
        $workflow->startReview($quote, auth()->user());
    }

    public function assign(QuoteWorkflow $workflow): void
    {
        $quote = $this->quote();
        $this->authorize('assign', $quote);
        $user = $this->assignee !== '' ? User::query()->find((int) $this->assignee) : null;
        $this->runSafely(fn () => $workflow->assign($quote, $user, auth()->user()), 'Assignment updated.');
    }

    public function addNote(QuoteWorkflow $workflow): void
    {
        $quote = $this->quote();
        $this->authorize('view', $quote);
        $this->validate(['note' => ['required', 'string', 'max:5000']]);
        $workflow->addNote($quote, auth()->user(), $this->note);
        $this->note = '';
    }

    public function save(QuoteWorkflow $workflow): bool
    {
        $quote = $this->quote();
        $this->authorize('update', $quote);

        $this->validate([
            'lines.*.quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'lines.*.unit_price' => ['nullable', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'lines.*.discount' => ['nullable', 'regex:/^\d{1,3}(\.\d{1,2})?$/'],
            'terms.discount' => ['nullable', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'terms.shipping' => ['nullable', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'terms.additional' => ['nullable', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'terms.additional_label' => ['nullable', 'string', 'max:120'],
            'terms.tax_rate' => ['nullable', 'regex:/^\d{1,2}(\.\d{1,2})?$/'],
            'terms.valid_until' => ['nullable', 'date'],
            'terms.payment_terms' => ['nullable', 'string', 'max:2000'],
            'terms.delivery_terms' => ['nullable', 'string', 'max:2000'],
            'terms.customer_notes' => ['nullable', 'string', 'max:5000'],
            'terms.po_reference' => ['nullable', 'string', 'max:80'],
        ], [
            'lines.*.unit_price.regex' => 'Enter a plain amount such as 12.50.',
            'terms.discount.regex' => 'Enter a plain amount such as 12.50.',
            'terms.shipping.regex' => 'Enter a plain amount such as 12.50.',
            'terms.additional.regex' => 'Enter a plain amount such as 12.50.',
            'lines.*.discount.regex' => 'Enter a percentage such as 5 or 7.5.',
            'terms.tax_rate.regex' => 'Enter a percentage such as 8.25.',
        ]);

        foreach ($this->lines as $id => $line) {
            if (Money::parsePercentToBps($line['discount'] ?? '') > 10000) {
                $this->addError("lines.{$id}.discount", 'A discount cannot exceed 100%.');

                return false;
            }
        }

        return $this->runSafely(function () use ($workflow, $quote) {
            $workflow->saveCommercials($quote, $this->parsedLines(), $this->parsedTerms(), auth()->user());
            $this->fillForm($quote->refresh());
        }, 'Quote saved.');
    }

    public function send(QuoteWorkflow $workflow): void
    {
        $quote = $this->quote();
        $this->authorize('update', $quote);
        if ($quote->status->isEditable() && ! $this->save($workflow)) {
            return;
        }
        $this->runSafely(function () use ($workflow, $quote) {
            $this->sentLink = $workflow->send($quote->refresh(), auth()->user());
        }, 'Quotation sent to '.$quote->email.'.');
        $this->fillForm($quote->refresh());
    }

    public function applySuggested(QuoteWorkflow $workflow): void
    {
        $quote = $this->quote();
        $this->authorize('update', $quote);
        $this->runSafely(function () use ($workflow, $quote) {
            $n = $workflow->applySuggestedPrices($quote, auth()->user());
            session()->flash('status', $n ? "Catalogue pricing applied to {$n} line(s)." : 'No unpriced lines with catalogue prices.');
            $this->fillForm($quote->refresh());
        });
    }

    public function addLine(QuoteWorkflow $workflow): void
    {
        $quote = $this->quote();
        $this->authorize('update', $quote);
        $this->validate(['newSku' => ['required', 'string', 'max:64'], 'newQty' => ['required', 'integer', 'min:1', 'max:1000000']]);

        $variant = ProductVariant::query()->where('sku', $this->newSku)->first();
        $product = $variant?->product ?? Product::query()->where('sku', $this->newSku)->first();
        if (! $product) {
            $this->addError('newSku', 'No product or variant with that SKU.');

            return;
        }
        $this->runSafely(function () use ($workflow, $quote, $product, $variant) {
            $workflow->addLine($quote, $product, $variant, (int) $this->newQty, auth()->user());
            $this->reset('newSku', 'newQty');
            $this->fillForm($quote->refresh());
        }, 'Line added.');
    }

    public function removeLine(int $itemId, QuoteWorkflow $workflow): void
    {
        $quote = $this->quote();
        $this->authorize('update', $quote);
        $item = $quote->items()->findOrFail($itemId);
        $this->runSafely(function () use ($workflow, $quote, $item) {
            $workflow->removeLine($quote, $item, auth()->user());
            $this->fillForm($quote->refresh());
        }, 'Line removed.');
    }

    public function reopen(QuoteWorkflow $workflow): void
    {
        $quote = $this->quote();
        $this->authorize('update', $quote);
        $this->runSafely(fn () => $workflow->reopen($quote, auth()->user()), 'Quote reopened for revision.');
    }

    public function convert(QuoteWorkflow $workflow)
    {
        $quote = $this->quote();
        $this->authorize('convert', $quote);
        try {
            $order = $workflow->convertToOrder($quote, auth()->user());
        } catch (ValidationException $e) {
            $this->addError('convert', collect($e->errors())->flatten()->first());

            return null;
        }
        session()->flash('status', "Sales order {$order->reference} created.");

        return $this->redirectRoute('admin.orders.show', $order);
    }

    private function runSafely(callable $fn, ?string $success = null): bool
    {
        try {
            $fn();
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, $messages[0]);
            }

            return false;
        }
        if ($success) {
            session()->flash('status', $success);
        }

        return true;
    }

    private function parsedLines(): array
    {
        return collect($this->lines)->map(fn ($l) => [
            'quantity' => (int) $l['quantity'],
            'unit_price_cents' => $this->money($l['unit_price'] ?? ''),
            'discount_bps' => Money::parsePercentToBps($l['discount'] ?? ''),
        ])->all();
    }

    private function parsedTerms(): array
    {
        return [
            'discount_cents' => $this->money($this->terms['discount']) ?? 0,
            'shipping_cents' => $this->money($this->terms['shipping']) ?? 0,
            'additional_charges_cents' => $this->money($this->terms['additional']) ?? 0,
            'additional_charges_label' => $this->terms['additional_label'] ?: null,
            'tax_rate_bps' => Money::parsePercentToBps($this->terms['tax_rate']),
            'valid_until' => $this->terms['valid_until'] ?: null,
            'payment_terms' => $this->terms['payment_terms'] ?: null,
            'delivery_terms' => $this->terms['delivery_terms'] ?: null,
            'customer_notes' => $this->terms['customer_notes'] ?: null,
            'po_reference' => $this->terms['po_reference'] ?: null,
        ];
    }

    private function money(?string $value): ?int
    {
        try {
            return Money::parse($value);
        } catch (InvalidArgumentException) {
            return null;
        }
    }

    public function render()
    {
        $quote = $this->quote()->load(['items.product', 'attachments', 'company.customerGroup', 'assignee', 'user', 'versions.author', 'salesOrder']);
        $user = auth()->user();

        // Live preview of totals from the unsaved form (server-side, integer maths).
        $preview = null;
        try {
            $previewLines = collect($this->parsedLines())->filter(fn ($l) => $l['unit_price_cents'] !== null)
                ->map(fn ($l) => ['unit_cents' => $l['unit_price_cents'], 'quantity' => max(1, $l['quantity']), 'discount_bps' => min(10000, $l['discount_bps'])]);
            $t = $this->parsedTerms();
            $preview = QuoteTotals::calculate($previewLines, $t['discount_cents'], $t['shipping_cents'], $t['additional_charges_cents'], min(10000, $t['tax_rate_bps']), (bool) setting('tax.on_shipping', true));
        } catch (InvalidArgumentException) {
            // Invalid percentage while typing; validation reports it on save.
        }

        return view('livewire.admin.quote-workspace', [
            'quote' => $quote,
            'preview' => $preview,
            'canEdit' => $user->can('update', $quote) && $quote->status->isEditable(),
            'canAssign' => $user->can('assign', $quote),
            'canConvert' => $user->can('convert', $quote),
            'staff' => User::staff()->orderBy('name')->pluck('name', 'id'),
            'activities' => $quote->activities()->with('user')->limit(50)->get(),
        ])->layout('components.layouts.admin', ['title' => $quote->reference]);
    }
}
