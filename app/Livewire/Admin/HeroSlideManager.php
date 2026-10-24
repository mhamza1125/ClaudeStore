<?php

namespace App\Livewire\Admin;

use App\Images\ImageStorage;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Admin → Homepage slides: content for the homepage banner slider. */
class HeroSlideManager extends Component
{
    use WithFileUploads;

    /** Relative site path (/products) or absolute http(s) URL. */
    private const URL_RULE = 'regex:#^(/[^\s]*|https?://[^\s]+)$#';

    public ?int $editingId = null;

    public array $form = [];

    public $image;

    public string $productSku = '';

    public function mount(): void
    {
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->image = null;
        $this->productSku = '';
        $this->form = [
            'eyebrow' => '', 'title' => '', 'subtitle' => '', 'image_alt' => '', 'image_focus' => 'center',
            'primary_label' => '', 'primary_url' => '', 'secondary_label' => '', 'secondary_url' => '',
            'is_active' => true, 'starts_at' => '', 'ends_at' => '',
        ];
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $slide = HeroSlide::query()->with('product')->findOrFail($id);
        $this->resetForm();
        $this->editingId = $slide->id;
        $this->productSku = (string) $slide->product?->sku;
        $this->form = [
            ...collect($slide->only(['eyebrow', 'title', 'subtitle', 'image_alt', 'image_focus', 'primary_label', 'primary_url', 'secondary_label', 'secondary_url']))->map(fn ($v) => (string) $v)->all(),
            'is_active' => $slide->is_active,
            'starts_at' => $slide->starts_at?->format('Y-m-d\TH:i') ?? '',
            'ends_at' => $slide->ends_at?->format('Y-m-d\TH:i') ?? '',
        ];
    }

    public function save(ImageStorage $storage): void
    {
        $this->validate([
            'form.eyebrow' => ['nullable', 'string', 'max:120'],
            'form.title' => ['required', 'string', 'max:160'],
            'form.subtitle' => ['nullable', 'string', 'max:400'],
            'form.image_alt' => ['nullable', 'string', 'max:200'],
            'form.image_focus' => ['required', Rule::in(array_keys(HeroSlide::FOCUS))],
            'form.primary_label' => ['nullable', 'required_with:form.primary_url', 'string', 'max:60'],
            'form.primary_url' => ['nullable', 'required_with:form.primary_label', 'max:255', self::URL_RULE],
            'form.secondary_label' => ['nullable', 'required_with:form.secondary_url', 'string', 'max:60'],
            'form.secondary_url' => ['nullable', 'required_with:form.secondary_label', 'max:255', self::URL_RULE],
            'form.starts_at' => ['nullable', 'date'],
            'form.ends_at' => ['nullable', 'date', 'after:form.starts_at'],
            'productSku' => ['nullable', 'string', Rule::exists('products', 'sku')],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:15360', 'dimensions:min_width=800,min_height=500'],
        ], [
            'form.primary_url.regex' => 'Use a site path such as /products or a full https:// address.',
            'form.secondary_url.regex' => 'Use a site path such as /quote or a full https:// address.',
            'productSku.exists' => 'No product with that SKU.',
            'image.dimensions' => 'Banner images should be at least 800 × 500 px (landscape works best).',
        ]);

        $slide = $this->editingId ? HeroSlide::query()->findOrFail($this->editingId) : new HeroSlide(['sort_order' => (int) HeroSlide::query()->max('sort_order') + 1]);
        $slide->fill([
            ...collect($this->form)->except(['is_active', 'starts_at', 'ends_at'])->map(fn ($v) => $v === '' ? null : $v)->all(),
            'image_focus' => $this->form['image_focus'],
            'is_active' => (bool) $this->form['is_active'],
            'starts_at' => $this->form['starts_at'] ?: null,
            'ends_at' => $this->form['ends_at'] ?: null,
            'product_id' => $this->productSku !== '' ? Product::query()->where('sku', $this->productSku)->value('id') : null,
        ]);

        if ($this->image) {
            [$path] = $storage->optimiseInto($this->image->get(), 'hero');
            $this->deleteOwnImage($slide);
            $slide->image_path = $path;
        }
        if (! $slide->image_path && ! $slide->product_id) {
            $this->addError('image', 'Upload a banner image or link a product whose photo should be used.');

            return;
        }

        $creating = ! $slide->exists;
        $slide->save();
        AuditLogger::log($creating ? 'hero_slide.created' : 'hero_slide.updated', $slide, "Homepage slide “{$slide->title}” saved.");
        session()->flash('status', 'Slide saved.');
        $this->resetForm();
    }

    public function removeImage(): void
    {
        $slide = HeroSlide::query()->findOrFail($this->editingId);
        $this->deleteOwnImage($slide);
        $slide->update(['image_path' => null]);
    }

    public function toggle(int $id): void
    {
        $slide = HeroSlide::query()->findOrFail($id);
        $slide->update(['is_active' => ! $slide->is_active]);
        AuditLogger::log('hero_slide.'.($slide->is_active ? 'activated' : 'deactivated'), $slide, $slide->title);
    }

    public function move(int $id, int $direction): void
    {
        $slides = HeroSlide::query()->orderBy('sort_order')->orderBy('id')->get()->values();
        $index = $slides->search(fn ($s) => $s->id === $id);
        $swap = $slides->get($index + $direction);
        if ($index === false || ! $swap) {
            return;
        }
        [$slides[$index], $slides[$index + $direction]] = [$swap, $slides[$index]];
        foreach ($slides as $order => $slide) {
            $slide->update(['sort_order' => $order]);
        }
    }

    public function delete(int $id): void
    {
        $slide = HeroSlide::query()->findOrFail($id);
        $this->deleteOwnImage($slide);
        AuditLogger::log('hero_slide.deleted', null, "Homepage slide “{$slide->title}” deleted.");
        $slide->delete();
        if ($this->editingId === $id) {
            $this->resetForm();
        }
    }

    private function deleteOwnImage(HeroSlide $slide): void
    {
        if ($slide->image_path && ! str_starts_with($slide->image_path, 'demo/')) {
            Storage::disk('public')->delete($slide->image_path);
        }
    }

    public function render()
    {
        return view('livewire.admin.hero-slide-manager', [
            'slides' => HeroSlide::query()->with('product.approvedImages')->orderBy('sort_order')->orderBy('id')->get(),
            'liveIds' => HeroSlide::query()->live()->pluck('id')->all(),
            'editing' => $this->editingId ? HeroSlide::query()->with('product.approvedImages')->find($this->editingId) : null,
        ])->layout('components.layouts.admin', ['title' => 'Homepage slides']);
    }
}
