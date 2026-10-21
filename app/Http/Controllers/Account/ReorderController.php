<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\SalesOrder;
use App\Services\QuoteBasketService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** Adds previous lines back to the quote list at today's prices (re-validated). */
class ReorderController extends Controller
{
    public function quote(Quote $quote, QuoteBasketService $basket)
    {
        $this->authorize('view', $quote);

        return $this->reorder($quote->items()->with(['product', 'variant'])->get(), $basket);
    }

    public function order(SalesOrder $order, QuoteBasketService $basket)
    {
        $user = auth()->user();
        abort_unless($order->user_id === $user->id || ($user->company_id && $order->company_id === $user->company_id), 404);

        return $this->reorder($order->items()->with(['product', 'variant'])->get(), $basket);
    }

    private function reorder(Collection $items, QuoteBasketService $basket)
    {
        $added = 0;
        $skipped = [];
        foreach ($items as $item) {
            if (! $item->product || ! $item->product->canBeQuoted()) {
                $skipped[] = $item->product_name;

                continue;
            }
            try {
                $basket->add($item->product, $item->variant?->is_active ? $item->variant : null, $item->quantity);
                $added++;
            } catch (ValidationException) {
                $skipped[] = $item->product_name;
            }
        }

        $redirect = redirect()->route('quote.basket')->with('status', "{$added} item(s) added to your quote list at current prices.");

        return $skipped ? $redirect->with('warning', 'Not available any more: '.implode(', ', array_unique($skipped)).'.') : $redirect;
    }
}
