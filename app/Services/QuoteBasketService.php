<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\QuoteBasket;
use App\Models\QuoteBasketItem;
use App\Models\User;
use App\Pricing\PriceResolver;
use App\Pricing\PriceResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * The quote list ("basket"). Guests are tracked by a random token in the session;
 * signed-in users by user_id. Guest items are merged into the user's list on login.
 * Prices are never stored here — they are resolved server-side on every read.
 */
class QuoteBasketService
{
    private const SESSION_KEY = 'quote_basket_token';

    public function __construct(private PriceResolver $resolver) {}

    public function current(bool $create = false): ?QuoteBasket
    {
        if ($user = auth()->user()) {
            return $create ? QuoteBasket::query()->firstOrCreate(['user_id' => $user->id]) : $user->basket()->first();
        }

        $token = session(self::SESSION_KEY);
        $basket = $token ? QuoteBasket::query()->where('token', $token)->whereNull('user_id')->first() : null;
        if (! $basket && $create) {
            $token = Str::random(48);
            session([self::SESSION_KEY => $token]);
            $basket = QuoteBasket::query()->create(['token' => $token]);
        }

        return $basket;
    }

    public function company(): ?Company
    {
        return auth()->user()?->company;
    }

    public function count(): int
    {
        return (int) ($this->current()?->items()->count() ?? 0);
    }

    /**
     * @throws ValidationException
     */
    public function add(Product $product, ?ProductVariant $variant, int $quantity, ?string $customization = null, ?string $notes = null): QuoteBasketItem
    {
        $this->assertQuotable($product, $variant);
        $basket = $this->current(create: true);

        $item = $basket->items()->where('product_id', $product->id)->where('variant_id', $variant?->id)->first();
        $newQty = $item ? $item->quantity + $quantity : $quantity;
        $this->assertQuantity($product, $variant, $newQty);

        if ($item) {
            $item->update(array_filter([
                'quantity' => $newQty,
                'customization' => $customization ?: $item->customization,
                'notes' => $notes ?: $item->notes,
            ], fn ($v) => $v !== null));

            return $item;
        }

        return $basket->items()->create([
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $quantity,
            'customization' => $customization,
            'notes' => $notes,
        ]);
    }

    /**
     * @throws ValidationException
     */
    public function update(int $itemId, int $quantity, ?string $customization, ?string $notes): QuoteBasketItem
    {
        $item = $this->findItem($itemId);
        $this->assertQuantity($item->product, $item->variant, $quantity, "items.{$itemId}.quantity");
        $item->update(['quantity' => $quantity, 'customization' => $customization, 'notes' => $notes]);

        return $item;
    }

    public function remove(int $itemId): void
    {
        $this->findItem($itemId)->delete();
    }

    public function clear(): void
    {
        $this->current()?->items()->delete();
    }

    /**
     * Basket lines with server-resolved pricing for the current buyer.
     *
     * @return Collection<int, array{item: QuoteBasketItem, price: PriceResult}>
     */
    public function lines(): Collection
    {
        $basket = $this->current();
        if (! $basket) {
            return collect();
        }
        $company = $this->company()?->loadMissing('customerGroup');

        return $basket->items()->with(['product.priceTiers', 'product.approvedImages', 'variant'])->get()
            ->filter(fn ($item) => $item->product !== null)
            ->map(fn (QuoteBasketItem $item) => [
                'item' => $item,
                'price' => $this->resolver->resolve($item->product, $item->variant, $item->quantity, $company),
            ])->values();
    }

    /** Indicative total, only when every line has a price visible to the buyer. */
    public function indicativeTotal(Collection $lines): ?int
    {
        if ($lines->isEmpty() || $lines->contains(fn ($l) => $l['price']->displayLineTotalCents() === null)) {
            return null;
        }

        return $lines->sum(fn ($l) => $l['price']->displayLineTotalCents());
    }

    public function mergeGuestBasketInto(User $user): void
    {
        $token = session(self::SESSION_KEY);
        if (! $token) {
            return;
        }
        $guest = QuoteBasket::query()->where('token', $token)->whereNull('user_id')->first();
        if ($guest) {
            $target = QuoteBasket::query()->firstOrCreate(['user_id' => $user->id]);
            foreach ($guest->items as $item) {
                $existing = $target->items()->where('product_id', $item->product_id)->where('variant_id', $item->variant_id)->first();
                $existing
                    ? $existing->update(['quantity' => max($existing->quantity, $item->quantity)])
                    : $item->update(['quote_basket_id' => $target->id]);
            }
            $guest->delete();
        }
        session()->forget(self::SESSION_KEY);
    }

    private function findItem(int $itemId): QuoteBasketItem
    {
        $basket = $this->current();
        $item = $basket?->items()->with(['product', 'variant'])->whereKey($itemId)->first();
        abort_if(! $item, 404);

        return $item;
    }

    private function assertQuotable(Product $product, ?ProductVariant $variant): void
    {
        if (! $product->canBeQuoted()) {
            throw ValidationException::withMessages(['quantity' => 'This product is not available for quotation.']);
        }
        if ($variant && ($variant->product_id !== $product->id || ! $variant->is_active)) {
            throw ValidationException::withMessages(['variant' => 'Please choose a valid option.']);
        }
        if (! $variant && $product->activeVariants()->exists()) {
            throw ValidationException::withMessages(['variant' => 'Please choose an option.']);
        }
    }

    private function assertQuantity(Product $product, ?ProductVariant $variant, int $quantity, string $key = 'quantity'): void
    {
        if ($quantity > 1_000_000) {
            throw ValidationException::withMessages([$key => 'For quantities above 1,000,000 please contact us directly.']);
        }
        $result = $this->resolver->resolve($product, $variant, $quantity, $this->company());
        if (! $result->isValidQuantity()) {
            throw ValidationException::withMessages([$key => $result->errors[0]]);
        }
    }
}
