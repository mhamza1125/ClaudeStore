<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['parent_id', 'name', 'slug', 'description', 'image_path', 'sort_order', 'is_active', 'is_featured'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'is_featured' => 'boolean'];
    }

    protected static function booted(): void
    {
        // Categories are listed in the cached sitemap (audit F-23).
        static::saved(fn () => \Illuminate\Support\Facades\Cache::forget(\App\Http\Controllers\SeoController::SITEMAP_CACHE_KEY));
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true);
    }

    public function scopeRoots(Builder $q): Builder
    {
        return $q->whereNull('parent_id');
    }

    /** IDs of this category and its direct subcategories (two-level tree). */
    public function selfAndChildIds(): array
    {
        return array_merge([$this->id], $this->children()->pluck('id')->all());
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path) : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
