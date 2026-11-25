<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A product's former slug; its URL answers with a 301 to the current product URL (audit F-11). */
class ProductSlugRedirect extends Model
{
    protected $fillable = ['old_slug', 'product_id'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
