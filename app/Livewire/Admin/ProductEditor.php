<?php

namespace App\Livewire\Admin;

use App\Enums\Availability;
use App\Enums\ImageViewType;
use App\Images\Contracts\ImageProvider;
use App\Images\ImageStorage;
use App\Jobs\OptimizeProductImage;
use App\Jobs\ProcessImageWithProvider;
use App\Models\Category;
use App\Models\CustomerGroup;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductImage;
use App\Services\AuditLogger;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

class ProductEditor extends Component
{
    use WithFileUploads;

    #[Locked]
    public ?int $productId = null;

    public array $form = [];

    /** key => [id, sku, name, color, size, finish, price, moq, stock_quantity, availability, is_active] */
    public array $variants = [];

    /** [variant (key|''), group (id|''), min_qty, price] */
    public array $tiers = [];

    public array $specs = [];

    // Image upload
    public $uploads = [];

    /** Optional: '' means the image does not show a specific view. */
    public string $uploadViewType = '';

    public string $uploadVariant = '';

    public bool $uploadAsDraft = false;

    public $specSheet;

    public function mount(?Product $product = null): void
    {
        $product = $product?->exists ? $product->load(['variants', 'priceTiers']) : null;
        $this->productId = $product?->id;

        $this->form = [
            'name' => $product?->name ?? '',
            'slug' => $product?->slug ?? '',
            'sku' => $product?->sku ?? '',
            'product_code' => $product?->product_code ?? '',
            'category_id' => (string) ($product?->category_id ?? ''),
            'short_description' => $product?->short_description ?? '',
            'description' => $product?->description ?? '',
            'material' => $product?->material ?? '',
            'leather_type' => $product?->leather_type ?? '',
            'dimensions' => $product?->dimensions ?? '',
            'hardware' => $product?->hardware ?? '',
            'finishing' => $product?->finishing ?? '',
            'packaging' => $product?->packaging ?? '',
            'lead_time' => $product?->lead_time ?? '',
            'base_price' => Money::toDecimalString($product?->base_price_cents),
            'moq' => $product?->moq ?? 1,
            'order_multiple' => $product?->order_multiple ?? 1,
            'status' => $product?->status ?? 'draft',
            'quote_enabled' => $product?->quote_enabled ?? true,
            'public_pricing' => $product?->public_pricing ?? true,
            'price_on_request' => $product?->price_on_request ?? false,
            'is_custom' => $product?->is_custom ?? false,
            'customizable' => $product?->customizable ?? false,
            'availability' => $product?->availability?->value ?? 'in_stock',
            'stock_quantity' => $product?->stock_quantity,
            'is_featured' => $product?->is_featured ?? false,
            'is_bestseller' => $product?->is_bestseller ?? false,
            'meta_title' => $product?->meta_title ?? '',
            'meta_description' => $product?->meta_description ?? '',
        ];

        $this->variants = [];
        foreach ($product?->variants ?? [] as $v) {
            $this->variants['id'.$v->id] = [
                'id' => $v->id, 'sku' => $v->sku, 'name' => $v->name, 'color' => (string) $v->color, 'size' => (string) $v->size,
                'finish' => (string) $v->finish, 'price' => Money::toDecimalString($v->price_cents), 'moq' => $v->moq,
                'stock_quantity' => $v->stock_quantity, 'availability' => $v->availability?->value ?? '', 'is_active' => $v->is_active,
            ];
        }
        $this->tiers = ($product?->priceTiers ?? collect())->map(fn (PriceTier $t) => [
            'variant' => $t->variant_id ? 'id'.$t->variant_id : '',
            'group' => (string) ($t->customer_group_id ?? ''),
            'min_qty' => $t->min_qty,
            'price' => Money::toDecimalString($t->unit_price_cents),
        ])->values()->all();
        $this->specs = $product?->specifications ?? [];
    }

    public function updatedFormName(string $value): void
    {
        if (! $this->productId && $this->form['slug'] === '') {
            $this->form['slug'] = Str::slug($value);
        }
    }

    public function addVariant(): void
    {
        $this->variants['new'.Str::random(6)] = ['id' => null, 'sku' => '', 'name' => '', 'color' => '', 'size' => '', 'finish' => '', 'price' => '', 'moq' => null, 'stock_quantity' => null, 'availability' => '', 'is_active' => true];
    }

    public function removeVariant(string $key): void
    {
        unset($this->variants[$key]);
        $this->tiers = array_values(array_filter($this->tiers, fn ($t) => $t['variant'] !== $key));
    }

    public function addTier(): void
    {
        $this->tiers[] = ['variant' => '', 'group' => '', 'min_qty' => '', 'price' => ''];
    }

    public function removeTier(int $i): void
    {
        unset($this->tiers[$i]);
        $this->tiers = array_values($this->tiers);
    }

    public function addSpec(): void
    {
        $this->specs[] = ['label' => '', 'value' => ''];
    }

    public function removeSpec(int $i): void
    {
        unset($this->specs[$i]);
        $this->specs = array_values($this->specs);
    }

    protected function rules(): array
    {
        $money = ['nullable', 'regex:/^\d{1,9}(\.\d{1,2})?$/'];

        return [
            'form.name' => ['required', 'string', 'max:255'],
            'form.slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('products', 'slug')->ignore($this->productId)],
            'form.sku' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('products', 'sku')->ignore($this->productId), Rule::unique('product_variants', 'sku')],
            'form.product_code' => ['nullable', 'string', 'max:64'],
            'form.category_id' => ['nullable', 'exists:categories,id'],
            'form.short_description' => ['nullable', 'string', 'max:500'],
            'form.description' => ['nullable', 'string', 'max:20000'],
            'form.material' => ['nullable', 'string', 'max:255'],
            'form.leather_type' => ['nullable', 'string', 'max:255'],
            'form.dimensions' => ['nullable', 'string', 'max:255'],
            'form.hardware' => ['nullable', 'string', 'max:255'],
            'form.finishing' => ['nullable', 'string', 'max:255'],
            'form.packaging' => ['nullable', 'string', 'max:255'],
            'form.lead_time' => ['nullable', 'string', 'max:255'],
            'form.base_price' => $money,
            'form.moq' => ['required', 'integer', 'min:1', 'max:1000000'],
            'form.order_multiple' => ['required', 'integer', 'min:1', 'max:10000'],
            'form.status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'form.availability' => ['required', Rule::enum(Availability::class)],
            'form.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'form.meta_title' => ['nullable', 'string', 'max:255'],
            'form.meta_description' => ['nullable', 'string', 'max:300'],
            'variants.*.sku' => ['required', 'string', 'max:64', 'regex:/^[A-Za-z0-9._-]+$/', 'distinct', Rule::unique('products', 'sku'),
                function ($attr, $value, $fail) {
                    $key = explode('.', $attr)[1];
                    $ownId = $this->variants[$key]['id'] ?? null;
                    if (DB::table('product_variants')->where('sku', $value)->when($ownId, fn ($q) => $q->where('id', '!=', $ownId))->exists()) {
                        $fail('This SKU is already used.');
                    }
                }],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.color' => ['nullable', 'string', 'max:80'],
            'variants.*.size' => ['nullable', 'string', 'max:80'],
            'variants.*.finish' => ['nullable', 'string', 'max:120'],
            'variants.*.price' => $money,
            'variants.*.moq' => ['nullable', 'integer', 'min:1'],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.availability' => ['nullable', Rule::enum(Availability::class)],
            'tiers.*.min_qty' => ['required', 'integer', 'min:1', 'max:10000000'],
            'tiers.*.price' => ['required', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'tiers.*.group' => ['nullable', 'exists:customer_groups,id'],
            'tiers.*.variant' => ['nullable', Rule::in(array_keys($this->variants))],
            'specs.*.label' => ['required', 'string', 'max:80'],
            'specs.*.value' => ['required', 'string', 'max:255'],
        ];
    }

    protected function messages(): array
    {
        return [
            'form.base_price.regex' => 'Enter an amount such as 18.00.',
            'variants.*.price.regex' => 'Enter an amount such as 18.00.',
            'tiers.*.price.regex' => 'Enter an amount such as 15.00.',
            'tiers.*.price.required' => 'Enter a unit price.',
            'tiers.*.min_qty.required' => 'Enter a minimum quantity.',
            'variants.*.sku.distinct' => 'Variant SKUs must be unique.',
            'variants.*.sku.unique' => 'This SKU is used by a product.',
            'form.sku.unique' => 'This SKU is already used.',
        ];
    }

    public function save()
    {
        $this->validate();

        // Duplicate tier thresholds within the same variant/group scope are ambiguous.
        $seen = [];
        foreach ($this->tiers as $i => $t) {
            $key = $t['variant'].'|'.$t['group'].'|'.(int) $t['min_qty'];
            if (isset($seen[$key])) {
                $this->addError("tiers.{$i}.min_qty", 'Duplicate threshold for the same option and group.');

                return null;
            }
            $seen[$key] = true;
        }

        $creating = ! $this->productId;
        $product = DB::transaction(function () {
            $product = $this->productId ? Product::query()->lockForUpdate()->findOrFail($this->productId) : new Product;
            $f = $this->form;
            $product->fill([
                ...collect($f)->only(['name', 'slug', 'sku', 'short_description', 'description', 'material', 'leather_type', 'dimensions', 'hardware', 'finishing', 'packaging', 'lead_time', 'meta_title', 'meta_description', 'product_code'])
                    ->map(fn ($v) => $v === '' ? null : $v)->all(),
                'category_id' => $f['category_id'] ?: null,
                'base_price_cents' => Money::parse($f['base_price']),
                'moq' => (int) $f['moq'],
                'order_multiple' => (int) $f['order_multiple'],
                'status' => $f['status'],
                'quote_enabled' => (bool) $f['quote_enabled'],
                'public_pricing' => (bool) $f['public_pricing'],
                'price_on_request' => (bool) $f['price_on_request'],
                'is_custom' => (bool) $f['is_custom'],
                'customizable' => (bool) $f['customizable'],
                'availability' => $f['availability'],
                'stock_quantity' => $f['stock_quantity'] === '' ? null : $f['stock_quantity'],
                'is_featured' => (bool) $f['is_featured'],
                'is_bestseller' => (bool) $f['is_bestseller'],
                'specifications' => array_values($this->specs) ?: null,
            ]);
            if ($product->status === 'published' && ! $product->published_at) {
                $product->published_at = now();
            }
            $changes = AuditLogger::diff($product, ['name', 'sku', 'base_price_cents', 'moq', 'order_multiple', 'status', 'public_pricing', 'price_on_request', 'quote_enabled']);
            $product->save();

            // Variants
            $keyToId = [];
            $keep = [];
            foreach ($this->variants as $key => $v) {
                $attrs = [
                    'sku' => $v['sku'], 'name' => $v['name'], 'color' => $v['color'] ?: null, 'size' => $v['size'] ?: null,
                    'finish' => $v['finish'] ?: null, 'price_cents' => Money::parse($v['price'] ?? ''),
                    'moq' => $v['moq'] ?: null, 'stock_quantity' => ($v['stock_quantity'] ?? '') === '' ? null : $v['stock_quantity'],
                    'availability' => $v['availability'] ?: null, 'is_active' => (bool) $v['is_active'],
                ];
                $variant = $v['id'] ? $product->variants()->findOrFail($v['id']) : $product->variants()->make();
                $variant->fill($attrs + ['sort_order' => count($keep)])->save();
                $keyToId[$key] = $variant->id;
                $keep[] = $variant->id;
            }
            $product->variants()->whereNotIn('id', $keep ?: [0])->delete();

            // Price tiers: replace the full set, recording old/new for the audit log.
            $oldTiers = $product->priceTiers()->get(['variant_id', 'customer_group_id', 'min_qty', 'unit_price_cents'])->toArray();
            $product->priceTiers()->delete();
            foreach ($this->tiers as $t) {
                $product->priceTiers()->create([
                    'variant_id' => $t['variant'] !== '' ? ($keyToId[$t['variant']] ?? null) : null,
                    'customer_group_id' => $t['group'] !== '' ? (int) $t['group'] : null,
                    'min_qty' => (int) $t['min_qty'],
                    'unit_price_cents' => Money::parse($t['price']),
                ]);
            }
            $newTiers = $product->priceTiers()->get(['variant_id', 'customer_group_id', 'min_qty', 'unit_price_cents'])->toArray();
            if ($oldTiers != $newTiers) {
                $changes['price_tiers'] = ['from' => $oldTiers, 'to' => $newTiers];
            }

            if ($changes || ! $this->productId) {
                AuditLogger::log($this->productId ? 'product.updated' : 'product.created', $product, "Product {$product->sku} saved.", $changes);
            }

            return $product;
        });

        session()->flash('status', 'Product saved.');
        if ($creating) {
            return $this->redirectRoute('admin.products.edit', $product);
        }
        $this->mount($product->fresh());

        return null;
    }

    public function duplicate()
    {
        $source = Product::query()->with(['variants', 'priceTiers'])->findOrFail($this->productId);
        $copy = DB::transaction(function () use ($source) {
            $suffix = '-COPY-'.Str::upper(Str::random(4));
            $copy = $source->replicate(['published_at']);
            $copy->fill(['name' => $source->name.' (copy)', 'slug' => $source->slug.'-copy-'.Str::lower(Str::random(4)), 'sku' => $source->sku.$suffix, 'status' => 'draft', 'is_demo' => false]);
            $copy->save();
            $map = [];
            foreach ($source->variants as $v) {
                $nv = $v->replicate();
                $nv->fill(['product_id' => $copy->id, 'sku' => $v->sku.$suffix])->save();
                $map[$v->id] = $nv->id;
            }
            foreach ($source->priceTiers as $t) {
                $copy->priceTiers()->create(['variant_id' => $t->variant_id ? $map[$t->variant_id] : null, 'customer_group_id' => $t->customer_group_id, 'min_qty' => $t->min_qty, 'unit_price_cents' => $t->unit_price_cents]);
            }
            AuditLogger::log('product.duplicated', $copy, "Duplicated from {$source->sku}.");

            return $copy;
        });
        session()->flash('status', 'Product duplicated as a draft. Images are not copied.');

        return $this->redirectRoute('admin.products.edit', $copy);
    }

    public function setStatus(string $status): void
    {
        abort_unless(in_array($status, ['draft', 'published', 'archived'], true), 422);
        $this->form['status'] = $status;
        $this->save();
    }

    // ---- Images ----

    public function uploadImages(ImageStorage $storage): void
    {
        $product = Product::query()->findOrFail($this->productId);
        $this->validate([
            'uploads' => ['required', 'array', 'max:10'],
            'uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:15360', 'dimensions:min_width=300,min_height=300'],
            'uploadViewType' => ['nullable', Rule::enum(ImageViewType::class)],
            'uploadVariant' => ['nullable', Rule::exists('product_variants', 'id')->where('product_id', $product->id)],
        ], ['uploads.*.dimensions' => 'Images must be at least 300 × 300 px.']);

        $view = ImageViewType::tryFrom($this->uploadViewType);
        $failed = 0;
        foreach ($this->uploads as $file) {
            $image = $storage->storeUpload($product, $file, [
                'view_type' => $view,
                'variant_id' => $this->uploadVariant ?: null,
                'alt' => $view ? $product->name.' — '.$view->label() : $product->name,
            ]);
            // Optimised in this request so the image is ready immediately, without a queue worker.
            // Manually uploaded edits/generated images can be marked as drafts for review.
            try {
                OptimizeProductImage::dispatchSync($image, $this->uploadAsDraft ? 'pending_review' : 'approved');
            } catch (Throwable $e) {
                // The job's failed() handler has marked the image failed with the reason; it can be retried.
                report($e);
                $failed++;
            }
        }
        $count = count($this->uploads);
        AuditLogger::log('product.images_uploaded', $product, $count.' image(s) uploaded.');
        $this->reset('uploads');
        if ($failed) {
            $this->addError('images', "{$failed} of {$count} image(s) could not be processed. See the note on the image, then retry or upload it again.");
        } else {
            session()->flash('status', $count === 1 ? 'Image uploaded and ready.' : "{$count} images uploaded and ready.");
        }
    }

    public function uploadSpecSheet(): void
    {
        $product = Product::query()->findOrFail($this->productId);
        $this->validate(['specSheet' => ['required', 'file', 'mimes:pdf', 'max:10240']]);
        if ($product->spec_sheet_path) {
            Storage::disk('public')->delete($product->spec_sheet_path);
        }
        $product->update(['spec_sheet_path' => $this->specSheet->storeAs("spec-sheets/{$product->id}", Str::uuid().'.pdf', 'public')]);
        $this->reset('specSheet');
        session()->flash('status', 'Specification sheet uploaded.');
    }

    public function removeSpecSheet(): void
    {
        $product = Product::query()->findOrFail($this->productId);
        if ($product->spec_sheet_path) {
            Storage::disk('public')->delete($product->spec_sheet_path);
            $product->update(['spec_sheet_path' => null]);
        }
    }

    public function updateImage(int $imageId, string $field, $value): void
    {
        $image = $this->image($imageId);
        match ($field) {
            'view_type' => $image->update(['view_type' => $value === '' ? null : ImageViewType::from($value)]),
            'variant_id' => $image->update(['variant_id' => $value !== '' && $image->product->variants()->whereKey((int) $value)->exists() ? (int) $value : null]),
            'alt' => $image->update(['alt' => Str::limit(strip_tags((string) $value), 250, '')]),
            default => abort(422),
        };
    }

    public function setImageStatus(int $imageId, string $status): void
    {
        abort_unless(in_array($status, ['approved', 'rejected', 'pending_review'], true), 422);
        $image = $this->image($imageId);
        abort_if($image->path === null, 422);
        $image->update(['status' => $status]);
        AuditLogger::log('product.image_'.$status, $image->product, "Image #{$image->id} marked {$status}.");
    }

    /**
     * Optimise an upload now, in this request: retries a failed upload, or unblocks one whose
     * queued job has not run (e.g. no queue worker). The result is a draft so staff check it
     * before it goes live, since the original draft/approved choice is not stored.
     */
    public function processImageNow(int $imageId): void
    {
        $image = $this->image($imageId);
        abort_unless($image->source === 'upload' && $image->original_path && in_array($image->status, ['processing', 'failed'], true), 422);

        $image->update(['status' => 'processing', 'provider_notes' => null]);
        try {
            OptimizeProductImage::dispatchSync($image, 'pending_review');
        } catch (Throwable $e) {
            // The job's failed() handler has already marked the image failed with the reason.
            report($e);
            $this->addError('images', "Image #{$image->id} could not be processed. See the note on the image, then retry or upload it again.");

            return;
        }
        AuditLogger::log('product.image_processed', $image->product, "Image #{$image->id} processed manually.");
        session()->flash('status', 'Image processed. Check it and approve it to publish.');
    }

    public function moveImage(int $imageId, int $direction): void
    {
        $image = $this->image($imageId);
        $images = $image->product->images()->get()->values();
        $index = $images->search(fn ($i) => $i->id === $image->id);
        $swap = $images->get($index + $direction);
        if ($swap) {
            $images[$index] = $swap;
            $images[$index + $direction] = $image;
            foreach ($images as $order => $img) {
                $img->update(['sort_order' => $order]);
            }
        }
    }

    public function deleteImage(int $imageId, ImageStorage $storage): void
    {
        $image = $this->image($imageId);
        AuditLogger::log('product.image_deleted', $image->product, "Image #{$image->id} ({$image->source}) deleted.");
        $storage->delete($image);
    }

    public function removeBackground(int $imageId, ImageProvider $provider): void
    {
        $source = $this->image($imageId);
        if (! $provider->isConfigured()) {
            $this->addError('images', 'No image provider is configured. Upload an edited image as a draft instead.');

            return;
        }
        $draft = $source->product->images()->create([
            'variant_id' => $source->variant_id, 'parent_id' => $source->id, 'disk' => ImageStorage::DISPLAY_DISK,
            'view_type' => $source->view_type, 'alt' => $source->alt, 'status' => 'processing', 'source' => 'processed',
            'provider' => $provider->name(), 'sort_order' => $source->sort_order,
        ]);
        ProcessImageWithProvider::dispatch($source, $draft);
        session()->flash('status', 'Sent to '.$provider->name().'. The result will appear as a draft for review.');
    }

    private function image(int $id): ProductImage
    {
        return ProductImage::query()->with('product')->where('product_id', $this->productId)->findOrFail($id);
    }

    public function render(ImageProvider $provider)
    {
        $product = $this->productId ? Product::query()->with(['images.variant', 'variants'])->find($this->productId) : null;

        return view('livewire.admin.product-editor', [
            'product' => $product,
            'categories' => Category::query()->orderBy('parent_id')->orderBy('sort_order')->get()->mapWithKeys(fn ($c) => [$c->id => ($c->parent_id ? '— ' : '').$c->name]),
            'groups' => CustomerGroup::query()->orderBy('name')->pluck('name', 'id'),
            'availabilities' => Availability::options(),
            'viewTypes' => ImageViewType::options(),
            'provider' => $provider,
        ])->layout('components.layouts.admin', ['title' => $product?->name ?? 'New product']);
    }
}
