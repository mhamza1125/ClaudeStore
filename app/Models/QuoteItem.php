<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteItem extends Model
{
    protected $fillable = [
        'quote_id', 'product_id', 'variant_id', 'sku', 'product_name', 'variant_name', 'product_snapshot',
        'quantity', 'customization', 'notes', 'indicative_unit_price_cents', 'indicative_price_source',
        'unit_price_cents', 'discount_bps', 'line_total_cents', 'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'product_snapshot' => 'array',
            'quantity' => 'integer',
            'indicative_unit_price_cents' => 'integer',
            'unit_price_cents' => 'integer',
            'discount_bps' => 'integer',
            'line_total_cents' => 'integer',
        ];
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
