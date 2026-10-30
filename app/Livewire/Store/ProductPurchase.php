<?php

namespace App\Livewire\Store;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Pricing\PriceAccess;
use App\Pricing\PriceResolver;
use App\Pricing\PriceResult;
use App\Services\QuoteBasketService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;

/**
 * Product detail purchase panel. Every quantity/variant change is re-priced on the
 * server by PriceResolver; nothing price-related is computed or trusted client-side.
 */
class ProductPurchase extends Component
{
    #[Locked]
    public int $productId;

    #[Locked]
    public bool $isFavorite = false;

    public ?int $variantId = null;

    public $quantity = 1;

    public string $customization = '';

    public string $notes = '';

    public bool $showCustomization = false;

    public ?string $added = null;

    public function mount(Product $product, bool $isFavorite = false): void
    {
        $this->productId = $product->id;
        $this->isFavorite = $isFavorite;
        $this->quantity = max(1, $product->moq);
        $this->variantId = $product->activeVariants->count() === 1 ? $product->activeVariants->first()->id : null;
    }

    public function updated($name): void
    {
        $this->added = null;
        if ($name === 'quantity') {
            $this->quantity = max(0, min(1_000_000, (int) $this->quantity));
        }
        $this->resetErrorBag($name === 'variantId' ? 'variant' : $name);
    }

    public function increment(int $by): void
    {
        $product = $this->product();
        $step = max(1, $product->order_multiple);
        $this->quantity = max(max(1, $product->moq), (int) $this->quantity + $by * $step);
        $this->added = null;
    }

    public function addToQuote(QuoteBasketService $basket): void
    {
        $this->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'customization' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $product = $this->product();
        $variant = $this->variantId ? ProductVariant::query()->where('product_id', $product->id)->find($this->variantId) : null;

        try {
            $basket->add($product, $variant, (int) $this->quantity, $this->customization ?: null, $this->notes ?: null);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                $this->addError($key, $messages[0]);
            }

            return;
        }

        $this->added = 'Added to your quote list.';
        $this->customization = '';
        $this->notes = '';
        $this->showCustomization = false;
        $this->dispatch('basket-updated');
    }

    public function product(): Product
    {
        return Product::query()->with(['priceTiers', 'activeVariants', 'approvedImages'])->findOrFail($this->productId);
    }

    public function render(PriceResolver $resolver, PriceAccess $priceAccess)
    {
        $product = $this->product();
        $variant = $this->variantId ? $product->activeVariants->firstWhere('id', $this->variantId) : null;
        $access = $priceAccess->current();
        $company = $access->company?->loadMissing('customerGroup');
        $price = $resolver->resolve($product, $variant, (int) $this->quantity, $company, access: $access);

        // Gallery: product-level images first, then those for the selected variant (or all variants if none chosen).
        $images = $product->approvedImages->filter(fn ($img) => $img->variant_id === null || $img->variant_id === $variant?->id);
        if ($variant) {
            $images = $images->sortBy(fn ($img) => $img->variant_id === $variant->id ? 0 : 1)->values();
        }

        return view('livewire.store.product-purchase', [
            'product' => $product,
            'variant' => $variant,
            'price' => $price,
            'images' => $images->values(),
            'company' => $company,
            'access' => $access,
        ]);
    }
}
