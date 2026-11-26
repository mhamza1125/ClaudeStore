<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
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

    /**
     * Rows whose validity window includes the business date $day (Y-m-d): valid_from <= $day <=
     * valid_until, both inclusive, open-ended when null (audit F-13). The single rule for the price
     * engine and the portal's Account pricing page.
     */
    public function scopeValidOn(Builder $query, string $day): Builder
    {
        return $query
            ->where(fn ($q) => $q->whereNull('valid_from')->orWhereDate('valid_from', '<=', $day))
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', $day));
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
