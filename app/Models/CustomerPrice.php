<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPrice extends Model
{
    protected $fillable = ['company_id', 'product_id', 'variant_id', 'min_qty', 'unit_price_cents', 'valid_from', 'valid_until', 'notes'];

    protected function casts(): array
    {
        return [
            'min_qty' => 'integer',
            'unit_price_cents' => 'integer',
            'valid_from' => 'date',
            'valid_until' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
