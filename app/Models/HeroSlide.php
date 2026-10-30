<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

/** Homepage banner slide, managed in Admin → Homepage slides. */
class HeroSlide extends Model
{
    use HasFactory;

    public const FOCUS = ['center' => 'Centre', 'top' => 'Top', 'bottom' => 'Bottom', 'left' => 'Left', 'right' => 'Right'];

    protected $fillable = [
        'eyebrow', 'title', 'subtitle', 'image_path', 'image_alt', 'product_id', 'image_focus', 'primary_label',
        'primary_url', 'secondary_label', 'secondary_url', 'is_active', 'sort_order', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'starts_at' => 'datetime', 'ends_at' => 'datetime'];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /** Active and inside its optional schedule window. */
    public function scopeLive(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where(fn ($w) => $w->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($w) => $w->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->orderBy('sort_order')->orderBy('id');
    }

    public function imageUrl(): ?string
    {
        if ($this->image_path) {
            return Storage::disk('public')->url($this->image_path);
        }
        if ($this->product && $this->product->isPublished()) {
            $images = $this->product->approvedImages;
            $image = $images->firstWhere('view_type.value', 'lifestyle') ?? $images->whereNull('variant_id')->first() ?? $images->first();

            return $image?->url('large');
        }

        return null;
    }

    public function imageAlt(): string
    {
        return $this->image_alt ?: ($this->product?->name ?? $this->title);
    }

    /**
     * Slides for the homepage. When none are configured or live, a single default slide
     * (not saved) keeps the banner meaningful, illustrated with a featured product.
     *
     * @return Collection<int, HeroSlide>
     */
    public static function forHomepage(): Collection
    {
        $slides = static::query()->live()->with('product.approvedImages')->limit(8)->get();
        if ($slides->isNotEmpty()) {
            return $slides;
        }

        $product = Product::published()->where('is_featured', true)->with('approvedImages')->first();

        return collect([static::defaultSlide($product)]);
    }

    public static function defaultSlide(?Product $product = null): self
    {
        $slide = new static([
            'eyebrow' => 'Manufacturer · Wholesaler · Private label',
            'title' => 'Leather goods, made for trade.',
            'subtitle' => 'Bags, wallets, belts and small leather goods for retailers, distributors and brands — with transparent volume pricing and quotations within one business day.',
            'primary_label' => 'Explore Products',
            'primary_url' => '/products',
            'secondary_label' => 'Request a Wholesale Quote',
            'secondary_url' => '/quote',
            'image_focus' => 'center',
        ]);
        $slide->setRelation('product', $product);
        $slide->product_id = $product?->id;

        return $slide;
    }
}
