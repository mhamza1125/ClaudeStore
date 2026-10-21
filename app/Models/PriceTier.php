<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PriceTier extends Model
{
    protected $fillable = ['product_id', 'variant_id', 'customer_group_id', 'min_qty', 'unit_price_cents'];

    protected function casts(): array
    {
        return ['min_qty' => 'integer', 'unit_price_cents' => 'integer'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function customerGroup(): BelongsTo
    {
        return $this->belongsTo(CustomerGroup::class);
    }
}
