<?php

namespace App\Livewire\Store;

use App\Enums\Availability;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\QuoteBasketService;
use App\Support\Money;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Catalog extends Component
{
    use WithPagination;

    public ?Category $category = null;

    #[Url(except: '')]
    public string $q = '';

    #[Url(except: [])]
    public array $material = [];

    #[Url(except: [])]
    public array $color = [];

    #[Url(except: [])]
    public array $availability = [];

    #[Url(except: '')]
    public string $priceMin = '';

    #[Url(except: '')]
    public string $priceMax = '';

    #[Url(except: 'featured')]
    public string $sort = 'featured';

    #[Url(except: 'grid')]
    public string $view = 'grid';

    /** Bulk entry (list view): productId => qty / variantId; selected productIds */
    public array $quantities = [];

    public array $variants = [];

    public array $selected = [];

    public array $bulkErrors = [];

    public ?string $bulkMessage = null;

    public function mount(?Category $category = null): void
    {
        // The container injects an empty model when no category is bound.
        $category = $category?->exists ? $category : null;
        abort_if($category && ! $category->is_active, 404);
        $this->category = $category;
    }

    public function updating($name): void
    {
        if (in_array(explode('.', $name)[0], ['q', 'material', 'color', 'availability', 'priceMin', 'priceMax', 'sort'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['q', 'material', 'color', 'availability', 'priceMin', 'priceMax']);
        $this->resetPage();
    }

    public function addSelected(QuoteBasketService $basket): void
    {
        $this->bulkErrors = [];
        $this->bulkMessage = null;
        $ids = array_map('intval', array_keys(array_filter($this->selected)));
        if (! $ids) {
            $this->bulkMessage = 'Select at least one product.';

            return;
        }

        $added = 0;
        $products = Product::published()->whereIn('id', $ids)->get()->keyBy('id');
        foreach ($ids as $id) {
            $product = $products->get($id);
            if (! $product) {
                continue;
            }
            $qty = (int) ($this->quantities[$id] ?? 0);
            $variant = filled($this->variants[$id] ?? null)
                ? ProductVariant::query()->where('product_id', $id)->find((int) $this->variants[$id])
                : null;
            try {
                $basket->add($product, $variant, $qty);
                $added++;
                unset($this->selected[$id]);
            } catch (ValidationException $e) {
                $this->bulkErrors[$id] = collect($e->errors())->flatten()->first();
            }
        }

        if ($added) {
            $this->dispatch('basket-updated');
            $this->bulkMessage = $added.' '.str('product')->plural($added).' added to your quote list.';
        }
    }

    public function render()
    {
        $base = Product::published();
        $query = (clone $base)
            ->with(['approvedImages', 'priceTiers', 'category', 'activeVariants'])
            ->when($this->category, fn (Builder $q) => $q->whereIn('category_id', $this->category->selfAndChildIds()))
            ->when(trim($this->q) !== '', fn (Builder $q) => $q->search(trim($this->q)))
            ->when($this->material, fn (Builder $q) => $q->whereIn('material', $this->material))
            ->when($this->color, fn (Builder $q) => $q->whereHas('activeVariants', fn (Builder $v) => $v->whereIn('color', $this->color)))
            ->when($this->availability, fn (Builder $q) => $q->whereIn('availability', $this->availability));

        // Price filter uses public list prices only, so hidden trade prices are never revealed.
        $min = $this->safeMoney($this->priceMin);
        $max = $this->safeMoney($this->priceMax);
        if ($min !== null || $max !== null) {
            $query->where('public_pricing', true)->where('price_on_request', false)->whereNotNull('base_price_cents')
                ->when($min !== null, fn ($q) => $q->where('base_price_cents', '>=', $min))
                ->when($max !== null, fn ($q) => $q->where('base_price_cents', '<=', $max));
        }

        $visiblePrice = 'CASE WHEN public_pricing = 1 AND price_on_request = 0 THEN base_price_cents ELSE NULL END';
        match ($this->sort) {
            'newest' => $query->orderByDesc('published_at'),
            'name' => $query->orderBy('name'),
            'price_asc' => $query->orderByRaw("CASE WHEN ({$visiblePrice}) IS NULL THEN 1 ELSE 0 END")->orderByRaw("{$visiblePrice} ASC")->orderBy('name'),
            'price_desc' => $query->orderByRaw("CASE WHEN ({$visiblePrice}) IS NULL THEN 1 ELSE 0 END")->orderByRaw("{$visiblePrice} DESC")->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderByDesc('is_bestseller')->orderBy('name'),
        };

        $scope = (clone $base)->when($this->category, fn (Builder $q) => $q->whereIn('category_id', $this->category->selfAndChildIds()));

        return view('livewire.store.catalog', [
            'products' => $query->paginate(12),
            'categories' => Category::active()->roots()->orderBy('sort_order')->with(['children' => fn ($q) => $q->where('is_active', true)])->get(),
            'materials' => (clone $scope)->whereNotNull('material')->distinct()->orderBy('material')->pluck('material'),
            'colors' => ProductVariant::query()->where('is_active', true)->whereNotNull('color')
                ->whereIn('product_id', (clone $scope)->select('id'))->distinct()->orderBy('color')->pluck('color'),
            'availabilities' => Availability::options(),
        ])->layout('components.layouts.store', [
            'title' => $this->category?->name ?? (trim($this->q) !== '' ? 'Search: '.$this->q : 'All products'),
            'description' => $this->category?->description ?? 'Wholesale leather bags, wallets, belts and accessories with volume pricing.',
            'canonical' => $this->category ? route('catalog.category', $this->category) : route('catalog'),
        ]);
    }

    private function safeMoney(string $value): ?int
    {
        try {
            return Money::parse($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
