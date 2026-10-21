<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Services\AuditLogger;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class CategoryManager extends Component
{
    use WithFileUploads;

    public ?int $editingId = null;

    public array $form = ['name' => '', 'slug' => '', 'parent_id' => '', 'description' => '', 'sort_order' => 0, 'is_active' => true, 'is_featured' => false];

    public $image;

    public function edit(int $id): void
    {
        $c = Category::query()->findOrFail($id);
        $this->editingId = $c->id;
        $this->form = ['name' => $c->name, 'slug' => $c->slug, 'parent_id' => (string) ($c->parent_id ?? ''), 'description' => (string) $c->description, 'sort_order' => $c->sort_order, 'is_active' => $c->is_active, 'is_featured' => $c->is_featured];
        $this->resetValidation();
    }

    public function cancel(): void
    {
        $this->reset('editingId', 'form', 'image');
        $this->resetValidation();
    }

    public function save(): void
    {
        if ($this->form['slug'] === '') {
            $this->form['slug'] = Str::slug($this->form['name']);
        }
        $this->validate([
            'form.name' => ['required', 'string', 'max:120'],
            'form.slug' => ['required', 'alpha_dash', 'max:120', Rule::unique('categories', 'slug')->ignore($this->editingId)],
            // Two levels only: a parent must itself be top-level and cannot be the category being edited.
            'form.parent_id' => ['nullable', Rule::exists('categories', 'id')->whereNull('parent_id'), Rule::notIn([$this->editingId])],
            'form.description' => ['nullable', 'string', 'max:500'],
            'form.sort_order' => ['integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        if ($this->editingId && $this->form['parent_id'] !== '' && Category::query()->where('parent_id', $this->editingId)->exists()) {
            $this->addError('form.parent_id', 'This category has subcategories, so it must stay top-level.');

            return;
        }

        $category = $this->editingId ? Category::query()->findOrFail($this->editingId) : new Category;
        $category->fill([...$this->form, 'parent_id' => $this->form['parent_id'] ?: null]);
        if ($this->image) {
            $category->image_path = $this->image->store('categories', 'public');
        }
        $category->save();
        AuditLogger::log($this->editingId ? 'category.updated' : 'category.created', $category, "Category {$category->name} saved.");
        Cache::forget('nav.categories');
        $this->cancel();
        session()->flash('status', 'Category saved.');
    }

    public function delete(int $id): void
    {
        $c = Category::query()->withCount(['products', 'children'])->findOrFail($id);
        if ($c->products_count || $c->children_count) {
            session()->flash('error', 'Move its products and subcategories first.');

            return;
        }
        AuditLogger::log('category.deleted', null, "Category {$c->name} deleted.");
        if ($c->image_path && ! str_starts_with($c->image_path, 'demo/')) {
            Storage::disk('public')->delete($c->image_path);
        }
        $c->delete();
        Cache::forget('nav.categories');
    }

    public function render()
    {
        return view('livewire.admin.category-manager', [
            'categories' => Category::query()->roots()->orderBy('sort_order')->with(['children' => fn ($q) => $q->withCount('products')])->withCount('products')->get(),
            'parents' => Category::query()->roots()->orderBy('name')->pluck('name', 'id'),
        ])->layout('components.layouts.admin', ['title' => 'Categories']);
    }
}
