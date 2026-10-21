<?php

namespace App\Livewire\Admin;

use App\Models\CustomerGroup;
use App\Services\AuditLogger;
use App\Support\Money;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;

class CustomerGroupManager extends Component
{
    public ?int $editingId = null;

    public array $form = ['name' => '', 'description' => '', 'discount' => '0', 'is_default' => false];

    public function edit(int $id): void
    {
        $g = CustomerGroup::query()->findOrFail($id);
        $this->editingId = $g->id;
        $this->form = ['name' => $g->name, 'description' => (string) $g->description, 'discount' => Money::bpsToPercentString($g->discount_bps), 'is_default' => $g->is_default];
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => ['required', 'string', 'max:120', Rule::unique('customer_groups', 'name')->ignore($this->editingId)],
            'form.description' => ['nullable', 'string', 'max:500'],
            'form.discount' => ['required', 'regex:/^\d{1,2}(\.\d{1,2})?$/'],
        ], ['form.discount.regex' => 'Enter a percentage between 0 and 99.99.']);

        $group = $this->editingId ? CustomerGroup::query()->findOrFail($this->editingId) : new CustomerGroup(['slug' => Str::slug($this->form['name']).'-'.Str::lower(Str::random(4))]);
        $group->fill(['name' => $this->form['name'], 'description' => $this->form['description'] ?: null, 'discount_bps' => Money::parsePercentToBps($this->form['discount']), 'is_default' => (bool) $this->form['is_default']]);
        $changes = AuditLogger::diff($group, ['discount_bps', 'is_default']);
        $group->save();
        if ($group->is_default) {
            CustomerGroup::query()->whereKeyNot($group->id)->update(['is_default' => false]);
        }
        AuditLogger::log('customer_group.saved', $group, "Customer group {$group->name} saved.", $changes);
        $this->reset('editingId', 'form');
    }

    public function delete(int $id): void
    {
        $g = CustomerGroup::query()->withCount('companies')->findOrFail($id);
        if ($g->companies_count) {
            session()->flash('error', 'Reassign its companies first.');

            return;
        }
        AuditLogger::log('customer_group.deleted', null, "Customer group {$g->name} deleted.");
        $g->delete();
    }

    public function render()
    {
        return view('livewire.admin.customer-group-manager', [
            'groups' => CustomerGroup::query()->withCount('companies')->orderBy('name')->get(),
        ])->layout('components.layouts.admin', ['title' => 'Customer groups']);
    }
}
