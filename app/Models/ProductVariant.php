<?php

namespace App\Models;

use App\Enums\Availability;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'sku', 'name', 'color', 'size', 'finish', 'price_cents', 'moq', 'stock_quantity',
        'availability', 'is_active', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'moq' => 'integer',
            'is_active' => 'boolean',
            'availability' => Availability::class,
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function priceTiers(): HasMany
    {
        return $this->hasMany(PriceTier::class, 'variant_id')->orderBy('min_qty');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'variant_id');
    }

    public function effectiveAvailability(): Availability
    {
        return $this->availability ?? $this->product->availability;
    }
}
