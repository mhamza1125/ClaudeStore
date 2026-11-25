<?php

namespace App\Livewire\Store;

use App\Services\QuoteBasketService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class QuoteBasketPage extends Component
{
    /** itemId => ['quantity' => int, 'customization' => string, 'notes' => string] */
    public array $edits = [];

    public ?string $message = null;

    public function mount(QuoteBasketService $basket): void
    {
        $this->syncEdits($basket);
    }

    public function save(int $itemId, QuoteBasketService $basket): void
    {
        $this->message = null;
        $data = $this->edits[$itemId] ?? null;
        abort_if($data === null, 404);
        $this->validate([
            "edits.{$itemId}.quantity" => ['required', 'integer', 'min:1', 'max:1000000'],
            "edits.{$itemId}.customization" => ['nullable', 'string', 'max:2000'],
            "edits.{$itemId}.notes" => ['nullable', 'string', 'max:2000'],
        ], [], ["edits.{$itemId}.quantity" => 'quantity']);

        try {
            $basket->update($itemId, (int) $data['quantity'], $data['customization'] ?: null, $data['notes'] ?: null);
            $this->message = 'Quote list updated.';
        } catch (ValidationException $e) {
            $this->addError("edits.{$itemId}.quantity", collect($e->errors())->flatten()->first());
        }
    }

    public function remove(int $itemId, QuoteBasketService $basket): void
    {
        $basket->remove($itemId);
        unset($this->edits[$itemId]);
        $this->message = 'Item removed.';
        $this->dispatch('basket-updated');
    }

    private function syncEdits(QuoteBasketService $basket): void
    {
        foreach ($basket->lines() as $line) {
            $item = $line['item'];
            $this->edits[$item->id] ??= [
                'quantity' => $item->quantity,
                'customization' => (string) $item->customization,
                'notes' => (string) $item->notes,
            ];
        }
    }

    public function render(QuoteBasketService $basket)
    {
        $lines = $basket->lines();
        $user = auth()->user()?->loadMissing('company');
        $minOrder = (int) setting('order.min_value_cents', 0);
        $total = $basket->indicativeTotal($lines);

        return view('livewire.store.quote-basket-page', [
            'lines' => $lines,
            'total' => $total,
            'minOrder' => $minOrder,
            'belowMinimum' => $minOrder > 0 && $total !== null && $total < $minOrder,
            'user' => $user,
            'company' => $user?->company,
            'guestInquiries' => app(\App\Pricing\PriceAccess::class)->guestInquiriesAllowed(),
        // noindex, not a robots.txt Disallow: a blocked page's noindex is never seen (audit F-10).
        ])->layout('components.layouts.store', ['title' => 'Quote list', 'noindex' => true]);
    }
}
