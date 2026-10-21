<?php

namespace App\Livewire\Admin;

use App\Models\Company;
use App\Models\CustomerGroup;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class CompanyIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $wholesale = '';

    #[Url(except: '')]
    public string $group = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.admin.company-index', [
            'companies' => Company::query()->with(['customerGroup', 'salesRep'])->withCount(['users', 'quotes'])
                ->when($this->search !== '', fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%")))
                ->when($this->wholesale !== '', fn ($q) => $q->where('wholesale_status', $this->wholesale))
                ->when($this->group !== '', fn ($q) => $q->where('customer_group_id', (int) $this->group))
                ->orderByRaw("CASE WHEN wholesale_status = 'pending' THEN 0 ELSE 1 END")->orderBy('name')->paginate(25),
            'groups' => CustomerGroup::query()->orderBy('name')->pluck('name', 'id'),
        ])->layout('components.layouts.admin', ['title' => 'Customers']);
    }
}
