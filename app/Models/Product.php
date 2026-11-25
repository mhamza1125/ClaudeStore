<?php

namespace App\Models;

use App\Enums\Availability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id', 'name', 'slug', 'sku', 'product_code', 'short_description', 'description', 'material',
        'leather_type', 'dimensions', 'hardware', 'finishing', 'packaging', 'lead_time', 'specifications',
        'base_price_cents', 'moq', 'order_multiple', 'status', 'quote_enabled', 'public_pricing',
        'price_on_request', 'is_custom', 'customizable', 'availability', 'stock_quantity', 'is_featured',
        'is_bestseller', 'is_raw_leather', 'is_demo', 'spec_sheet_path', 'meta_title', 'meta_description', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'specifications' => 'array',
            'base_price_cents' => 'integer',
            'moq' => 'integer',
            'order_multiple' => 'integer',
            'quote_enabled' => 'boolean',
            'public_pricing' => 'boolean',
            'price_on_request' => 'boolean',
            'is_custom' => 'boolean',
            'customizable' => 'boolean',
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_raw_leather' => 'boolean',
            'is_demo' => 'boolean',
            'availability' => Availability::class,
            'published_at' => 'datetime',
        ];
    }

    /**
     * Raw leather is never priced online: it is always "price on request" and quotable,
     * however it was saved (editor, bulk action or CSV import).
     */
    protected static function booted(): void
    {
        static::saving(function (Product $product) {
            if ($product->is_raw_leather) {
                $product->price_on_request = true;
                $product->quote_enabled = true;
            }
        });

        // Remember the old slug so its URL keeps working with a 301 (audit F-11). Covers the
        // editor and the CSV import alike. Existing redirect rows are re-pointed, never deleted.
        static::updated(function (Product $product) {
            if ($product->wasChanged('slug') && filled($old = $product->getOriginal('slug'))) {
                ProductSlugRedirect::query()->updateOrInsert(['old_slug' => $old], ['product_id' => $product->id, 'updated_at' => now(), 'created_at' => now()]);
            }
            Cache::forget(\App\Http\Controllers\SeoController::SITEMAP_CACHE_KEY);
        });
        static::created(fn () => Cache::forget(\App\Http\Controllers\SeoController::SITEMAP_CACHE_KEY));
    }

    public function slugRedirects(): HasMany
    {
        return $this->hasMany(ProductSlugRedirect::class);
    }

    /** Was this product ever live? (archived products that were never published stay private) */
    public function wasEverPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order')->orderBy('id');
    }

    public function activeVariants(): HasMany
    {
        return $this->variants()->where('is_active', true);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('sort_order')->orderBy('id');
    }

    public function approvedImages(): HasMany
    {
        return $this->images()->where('status', 'approved');
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class)->orderBy('min_qty');
    }

    public function customerPrices(): HasMany
    {
        return $this->hasMany(CustomerPrice::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', 'published');
    }

    /**
     * Name, SKU, product code or variant SKU contains the term. Shared by the catalogue
     * and the header's live suggestions so both always return the same products.
     */
    public function scopeSearch(Builder $q, string $term): Builder
    {
        $like = like_term($term);

        return $q->where(fn (Builder $w) => $w->where('name', 'like', $like)->orWhere('sku', 'like', $like)
            ->orWhere('product_code', 'like', $like)
            // Inactive variants are not offered, so their SKUs do not match (owner decision D4).
            ->orWhereHas('activeVariants', fn (Builder $v) => $v->where('sku', 'like', $like)));
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public function canBeQuoted(): bool
    {
        return $this->isPublished() && $this->quote_enabled;
    }

    public function primaryImage(): ?ProductImage
    {
        $images = $this->relationLoaded('approvedImages') ? $this->approvedImages : $this->approvedImages()->get();

        return $images->first();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
