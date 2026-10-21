<?php

namespace App\Models;

use App\Enums\ImageViewType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id', 'variant_id', 'parent_id', 'disk', 'path', 'original_disk', 'original_path', 'view_type', 'alt',
        'status', 'source', 'provider', 'provider_notes', 'width', 'height', 'sort_order',
    ];

    protected function casts(): array
    {
        return ['view_type' => ImageViewType::class];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'parent_id');
    }

    public function derivatives(): HasMany
    {
        return $this->hasMany(ProductImage::class, 'parent_id');
    }

    public function url(): ?string
    {
        return $this->path ? Storage::disk($this->disk)->url($this->path) : null;
    }

    public function isDraft(): bool
    {
        return $this->status === 'pending_review';
    }
}
