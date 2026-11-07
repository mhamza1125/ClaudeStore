<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductIndex extends Component
{
    use WithPagination;

    /** Bulk actions: key => [label, attributes to set]. Category moves are handled separately. */
    public const BULK_ACTIONS = [
        'publish' => ['Publish', ['status' => 'published']],
        'draft' => ['Move to draft', ['status' => 'draft']],
        'archive' => ['Archive', ['status' => 'archived']],
        'feature' => ['Mark as featured', ['is_featured' => true]],
        'unfeature' => ['Remove from featured', ['is_featured' => false]],
        'bestseller' => ['Mark as bestseller', ['is_bestseller' => true]],
        'unbestseller' => ['Remove from bestsellers', ['is_bestseller' => false]],
        'raw_leather' => ['Add to raw leather gallery', ['is_raw_leather' => true]],
        'unraw_leather' => ['Remove from raw leather gallery', ['is_raw_leather' => false]],
        'price_on_request' => ['Set price on request', ['price_on_request' => true]],
        'show_price' => ['Show prices (turn off price on request)', ['price_on_request' => false]],
        'quote_on' => ['Enable quote requests', ['quote_enabled' => true]],
        'quote_off' => ['Disable quote requests', ['quote_enabled' => false]],
    ];

    private const MAX_BULK = 500;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $category = '';

    #[Url(except: '')]
    public string $type = '';

    /** @var array<int, string> Selected product ids (kept across pages). */
    public array $selected = [];

    public string $bulkAction = '';

    public string $bulkCategory = '';

    /** Result of the last bulk action (shown above the table). */
    public string $notice = '';

    public function updating(string $name): void
    {
        // Changing a filter returns to page 1 and drops a selection the admin can no longer see.
        if (in_array($name, ['search', 'status', 'category', 'type'], true)) {
            $this->resetPage();
            $this->selected = [];
        }
    }

    public function setStatus(int $id, string $status): void
    {
        abort_unless(in_array($status, ['draft', 'published', 'archived'], true), 422);
        $product = Product::query()->findOrFail($id);
        $from = $product->status;
        $product->update(['status' => $status, 'published_at' => $product->published_at ?? ($status === 'published' ? now() : null)]);
        AuditLogger::log('product.status', $product, "Status {$from} → {$status}.", ['status' => ['from' => $from, 'to' => $status]]);
    }

    /** Header checkbox: select every product on the current page, or clear them if all already are. */
    public function togglePage(array $ids): void
    {
        $ids = array_map('strval', array_filter(array_map('intval', $ids)));
        $this->selected = array_values(array_diff($ids, $this->selected) === []
            ? array_diff($this->selected, $ids)
            : array_unique([...$this->selected, ...$ids]));
    }

    /** Select every product matching the current filters (all pages). */
    public function selectAllMatching(): void
    {
        $this->selected = $this->filtered()->limit(self::MAX_BULK)->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function clearSelection(): void
    {
        $this->selected = [];
    }

    public function applyBulk(): void
    {
        $this->notice = '';
        $ids = array_values(array_unique(array_filter(array_map('intval', $this->selected))));
        if ($ids === []) {
            $this->addError('bulkAction', 'Select at least one product.');

            return;
        }
        if (count($ids) > self::MAX_BULK) {
            $this->addError('bulkAction', 'Bulk actions are limited to '.self::MAX_BULK.' products at a time.');

            return;
        }

        if ($this->bulkAction === 'category') {
            $this->validate(['bulkCategory' => ['required', 'exists:categories,id']], ['bulkCategory.required' => 'Choose a category.']);
            $attributes = ['category_id' => (int) $this->bulkCategory];
            $label = 'Moved to '.Category::query()->whereKey($attributes['category_id'])->value('name');
        } elseif (isset(self::BULK_ACTIONS[$this->bulkAction])) {
            [$label, $attributes] = self::BULK_ACTIONS[$this->bulkAction];
        } else {
            $this->addError('bulkAction', 'Choose an action.');

            return;
        }

        // Saved one by one so model rules (raw leather is always price-on-request) and the audit trail apply.
        $changed = DB::transaction(function () use ($ids, $attributes, $label) {
            $changed = 0;
            foreach (Product::query()->whereKey($ids)->lockForUpdate()->get() as $product) {
                $product->fill($attributes);
                if ($product->status === 'published' && ! $product->published_at) {
                    $product->published_at = now();
                }
                $diff = AuditLogger::diff($product, array_keys($attributes));
                if ($product->isDirty()) {
                    $product->save();
                    AuditLogger::log('product.bulk', $product, "Bulk action: {$label}.", $diff);
                    $changed++;
                }
            }

            return $changed;
        });

        $count = count($ids);
        $this->notice = ("{$label}: {$changed} of {$count} selected ".str('product')->plural($count).' updated'.($changed < $count ? ' (the rest already matched).' : '.'));
        $this->reset('selected', 'bulkAction', 'bulkCategory');
    }

    private function filtered()
    {
        return Product::query()
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', like_term($this->search))->orWhere('sku', 'like', like_term($this->search))))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->category !== '', fn ($q) => $q->where('category_id', (int) $this->category))
            ->when($this->type === 'raw', fn ($q) => $q->where('is_raw_leather', true))
            ->when($this->type === 'finished', fn ($q) => $q->where('is_raw_leather', false));
    }

    public function render()
    {
        $products = $this->filtered()->with(['category', 'approvedImages'])->withCount(['variants', 'priceTiers'])
            ->latest('id')->paginate(25);

        $pageIds = $products->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->all();

        return view('livewire.admin.product-index', [
            'products' => $products,
            'pageIds' => $pageIds,
            'pageSelected' => $pageIds !== [] && array_diff($pageIds, $this->selected) === [],
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id'),
            'bulkActions' => collect(self::BULK_ACTIONS)->map(fn ($a) => $a[0])->put('category', 'Move to category…')->all(),
        ])->layout('components.layouts.admin', ['title' => 'Products']);
    }
}
