<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Services\AuditLogger;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class ProductIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $category = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function setStatus(int $id, string $status): void
    {
        abort_unless(in_array($status, ['draft', 'published', 'archived'], true), 422);
        $product = Product::query()->findOrFail($id);
        $from = $product->status;
        $product->update(['status' => $status, 'published_at' => $product->published_at ?? ($status === 'published' ? now() : null)]);
        AuditLogger::log('product.status', $product, "Status {$from} → {$status}.", ['status' => ['from' => $from, 'to' => $status]]);
    }

    public function render()
    {
        $products = Product::query()->with(['category', 'approvedImages'])->withCount(['variants', 'priceTiers'])
            ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', like_term($this->search))->orWhere('sku', 'like', like_term($this->search))))
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->category !== '', fn ($q) => $q->where('category_id', (int) $this->category))
            ->latest('id')->paginate(25);

        return view('livewire.admin.product-index', [
            'products' => $products,
            'categories' => Category::query()->orderBy('name')->pluck('name', 'id'),
        ])->layout('components.layouts.admin', ['title' => 'Products']);
    }
}
