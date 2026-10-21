<?php

namespace App\Livewire\Admin;

use App\Enums\QuoteStatus;
use App\Models\Quote;
use App\Models\User;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class QuoteIndex extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $country = '';

    #[Url(except: '')]
    public string $assigned = '';

    #[Url(except: '')]
    public string $from = '';

    #[Url(except: '')]
    public string $to = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();
        $quotes = Quote::query()->visibleTo($user)->with(['assignee', 'company'])->withCount('items')
            ->when($this->search !== '', function ($q) {
                $term = '%'.str_replace(['%', '_'], ['\%', '\_'], $this->search).'%';
                $q->where(fn ($w) => $w->where('reference', 'like', $term)->orWhere('company_name', 'like', $term)
                    ->orWhere('email', 'like', $term)->orWhere('contact_name', 'like', $term));
            })
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->country !== '', fn ($q) => $q->where('country', $this->country))
            ->when($this->assigned === 'none', fn ($q) => $q->whereNull('assigned_to'))
            ->when(ctype_digit($this->assigned), fn ($q) => $q->where('assigned_to', (int) $this->assigned))
            ->when($this->from !== '' && strtotime($this->from), fn ($q) => $q->whereDate('created_at', '>=', $this->from))
            ->when($this->to !== '' && strtotime($this->to), fn ($q) => $q->whereDate('created_at', '<=', $this->to))
            ->latest('id')
            ->paginate(20);

        return view('livewire.admin.quote-index', [
            'quotes' => $quotes,
            'statuses' => QuoteStatus::options(),
            'staff' => User::staff()->orderBy('name')->pluck('name', 'id'),
            'countries' => Quote::query()->visibleTo($user)->distinct()->orderBy('country')->pluck('country')->mapWithKeys(fn ($c) => [$c => countries()[$c] ?? $c]),
        ])->layout('components.layouts.admin', ['title' => 'Quotes']);
    }
}
