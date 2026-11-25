<?php

namespace App\Livewire\Admin;

use App\Mail\CustomerAccessChanged;
use App\Models\User;
use App\Pricing\PriceAccess;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Admin → Customer accounts: B2B price access, approval, verification and suspension per user. */
class CustomerAccounts extends Component
{
    use WithPagination;

    public const STATUSES = [
        'pending' => 'Awaiting approval',
        'rejected' => 'Rejected',
        'unverified' => 'Email not verified',
        'suspended' => 'Suspended',
        'deny' => 'Prices blocked',
        'allow' => 'Prices always shown',
    ];

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    public function updating(): void
    {
        $this->resetPage();
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()->can('customers.manage'), 403);
    }

    private function customer(int $id): User
    {
        return User::query()->role('customer')->findOrFail($id);
    }

    public function approve(int $id): void
    {
        $this->setApproval($id, 'approved');
    }

    public function reject(int $id): void
    {
        $this->setApproval($id, 'rejected');
    }

    private function setApproval(int $id, string $status): void
    {
        $this->authorizeManage();
        $user = $this->customer($id);
        $from = $user->approval_status;
        $user->update(['approval_status' => $status, 'approved_at' => $status === 'approved' ? now() : null, 'approved_by' => auth()->id()]);
        AuditLogger::log('customer.'.$status, $user, "{$user->email}: approval {$from} → {$status}.", ['approval_status' => ['from' => $from, 'to' => $status]]);
        Mail::to($user->email, $user->name)->queue(new CustomerAccessChanged($user));
        session()->flash('status', $status === 'approved' ? "{$user->email} approved." : "{$user->email} rejected.");
    }

    public function markVerified(int $id): void
    {
        $this->authorizeManage();
        $user = $this->customer($id);
        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            AuditLogger::log('customer.email_verified_manually', $user, "{$user->email} marked as verified by staff.");
        }
    }

    public function resendVerification(int $id): void
    {
        $this->authorizeManage();
        $user = $this->customer($id);
        if (! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
            session()->flash('status', "Verification email sent to {$user->email}.");
        }
    }

    public function setPriceAccess(int $id, string $value): void
    {
        $this->authorizeManage();
        abort_unless(array_key_exists($value, User::PRICE_ACCESS), 422);
        $user = $this->customer($id);
        $from = $user->price_access;
        $user->update(['price_access' => $value]);
        AuditLogger::log('customer.price_access', $user, "{$user->email}: price access {$from} → {$value}.", ['price_access' => ['from' => $from, 'to' => $value]]);
    }

    public function toggleActive(int $id): void
    {
        $this->authorizeManage();
        $user = $this->customer($id);
        $user->update(['is_active' => ! $user->is_active]);
        AuditLogger::log($user->is_active ? 'customer.reactivated' : 'customer.suspended', $user, "{$user->email} ".($user->is_active ? 'reactivated' : 'suspended').'.');
        session()->flash('status', "{$user->email} ".($user->is_active ? 'reactivated.' : 'suspended; they are signed out on their next request.'));
    }

    public function render(PriceAccess $access)
    {
        $customers = User::query()->role('customer')->with('company')
            ->when($this->search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w->where('name', 'like', like_term($this->search))
                ->orWhere('email', 'like', like_term($this->search))
                ->orWhereHas('company', fn (Builder $c) => $c->where('name', 'like', like_term($this->search)))))
            ->when($this->status !== '', fn (Builder $q) => match ($this->status) {
                'pending', 'rejected' => $q->where('approval_status', $this->status),
                'unverified' => $q->whereNull('email_verified_at'),
                'suspended' => $q->where('is_active', false),
                'deny', 'allow' => $q->where('price_access', $this->status),
                default => $q,
            })
            ->orderByRaw("CASE WHEN approval_status = 'pending' THEN 0 ELSE 1 END")->latest('id')->paginate(25);

        return view('livewire.admin.customer-accounts', [
            'customers' => $customers,
            'access' => $access,
            'canManage' => auth()->user()->can('customers.manage'),
            'method' => $access->enabled() ? PriceAccess::METHODS[$access->method()] : null,
        ])->layout('components.layouts.admin', ['title' => 'Customer accounts']);
    }
}
