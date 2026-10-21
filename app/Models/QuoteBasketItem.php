<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteBasketItem extends Model
{
    protected $fillable = ['quote_basket_id', 'product_id', 'variant_id', 'quantity', 'customization', 'notes'];

    protected function casts(): array
    {
        return ['quantity' => 'integer'];
    }

    public function basket(): BelongsTo
    {
        return $this->belongsTo(QuoteBasket::class, 'quote_basket_id');
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
