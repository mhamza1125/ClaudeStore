<?php

namespace App\Livewire\Admin;

use App\Mail\WholesaleStatusChanged;
use App\Models\Company;
use App\Models\CustomerGroup;
use App\Models\CustomerPrice;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\AuditLogger;
use App\Support\Money;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CompanyShow extends Component
{
    #[Locked]
    public int $companyId;

    public string $groupId = '';

    public string $salesRepId = '';

    public string $paymentTerms = '';

    public string $internalNotes = '';

    public array $price = ['sku' => '', 'min_qty' => 1, 'unit_price' => '', 'valid_from' => '', 'valid_until' => '', 'notes' => ''];

    public string $memberEmail = '';

    public function mount(Company $company): void
    {
        $this->companyId = $company->id;
        $this->groupId = (string) ($company->customer_group_id ?? '');
        $this->salesRepId = (string) ($company->sales_rep_id ?? '');
        $this->paymentTerms = (string) $company->payment_terms;
        $this->internalNotes = (string) $company->internal_notes;
    }

    private function company(): Company
    {
        return Company::query()->findOrFail($this->companyId);
    }

    private function authorizeManage(): void
    {
        abort_unless(auth()->user()->can('customers.manage'), 403);
    }

    public function setWholesale(string $status): void
    {
        $this->authorizeManage();
        abort_unless(in_array($status, ['approved', 'rejected', 'pending', 'none'], true), 422);
        $company = $this->company();
        $from = $company->wholesale_status;
        $company->update(['wholesale_status' => $status, 'wholesale_reviewed_at' => now(), 'wholesale_reviewed_by' => auth()->id()]);
        AuditLogger::log('company.wholesale_'.$status, $company, "Wholesale status {$from} → {$status}.", ['wholesale_status' => ['from' => $from, 'to' => $status]]);

        if (in_array($status, ['approved', 'rejected'], true) && ($to = $company->email ?: $company->users()->where('company_role', 'owner')->value('email'))) {
            Mail::to($to)->queue(new WholesaleStatusChanged($company));
        }
        session()->flash('status', 'Wholesale status updated.');
    }

    public function saveAccount(): void
    {
        $this->authorizeManage();
        $this->validate([
            'groupId' => ['nullable', 'exists:customer_groups,id'],
            'salesRepId' => ['nullable', Rule::in(User::staff()->pluck('id')->map(fn ($id) => (string) $id)->all())],
            'paymentTerms' => ['nullable', 'string', 'max:255'],
            'internalNotes' => ['nullable', 'string', 'max:5000'],
        ]);
        $company = $this->company();
        $company->fill([
            'customer_group_id' => $this->groupId ?: null,
            'sales_rep_id' => $this->salesRepId ?: null,
            'payment_terms' => $this->paymentTerms ?: null,
            'internal_notes' => $this->internalNotes ?: null,
        ]);
        $changes = AuditLogger::diff($company, ['customer_group_id', 'sales_rep_id', 'payment_terms']);
        $company->save();
        if ($changes) {
            AuditLogger::log('company.updated', $company, 'Account settings changed.', $changes);
        }
        session()->flash('status', 'Account saved.');
    }

    public function addPrice(): void
    {
        $this->authorizeManage();
        $this->validate([
            'price.sku' => ['required', 'string'],
            'price.min_qty' => ['required', 'integer', 'min:1'],
            'price.unit_price' => ['required', 'regex:/^\d{1,9}(\.\d{1,2})?$/'],
            'price.valid_from' => ['nullable', 'date'],
            'price.valid_until' => ['nullable', 'date', 'after_or_equal:price.valid_from'],
            'price.notes' => ['nullable', 'string', 'max:255'],
        ], ['price.unit_price.regex' => 'Enter an amount such as 12.50.']);

        $variant = ProductVariant::query()->where('sku', $this->price['sku'])->first();
        $product = $variant?->product ?? Product::query()->where('sku', $this->price['sku'])->first();
        if (! $product) {
            $this->addError('price.sku', 'No product or variant with that SKU.');

            return;
        }
        $row = CustomerPrice::create([
            'company_id' => $this->companyId, 'product_id' => $product->id, 'variant_id' => $variant?->id,
            'min_qty' => (int) $this->price['min_qty'], 'unit_price_cents' => Money::parse($this->price['unit_price']),
            'valid_from' => $this->price['valid_from'] ?: null, 'valid_until' => $this->price['valid_until'] ?: null,
            'notes' => $this->price['notes'] ?: null,
        ]);
        AuditLogger::log('company.negotiated_price_added', $this->company(), "Negotiated price for {$this->price['sku']}.", ['unit_price_cents' => ['from' => null, 'to' => $row->unit_price_cents], 'min_qty' => $row->min_qty]);
        $this->reset('price');
        session()->flash('status', 'Negotiated price added.');
    }

    public function deletePrice(int $id): void
    {
        $this->authorizeManage();
        $row = CustomerPrice::query()->where('company_id', $this->companyId)->findOrFail($id);
        AuditLogger::log('company.negotiated_price_removed', $this->company(), "Negotiated price #{$row->id} removed.", ['unit_price_cents' => ['from' => $row->unit_price_cents, 'to' => null]]);
        $row->delete();
    }

    public function addMember(): void
    {
        $this->authorizeManage();
        $this->validate(['memberEmail' => ['required', 'email']]);
        $user = User::query()->where('email', strtolower($this->memberEmail))->first();
        if (! $user || $user->isStaff()) {
            $this->addError('memberEmail', 'No customer account with that email. Ask them to register first.');

            return;
        }
        if ($user->company_id && $user->company_id !== $this->companyId) {
            $this->addError('memberEmail', 'That user already belongs to another company.');

            return;
        }
        $user->update(['company_id' => $this->companyId, 'company_role' => $user->company_role ?? 'member']);
        AuditLogger::log('company.member_added', $this->company(), "{$user->email} added to company.");
        $this->reset('memberEmail');
    }

    public function setMemberRole(int $userId, string $role): void
    {
        $this->authorizeManage();
        abort_unless(in_array($role, ['owner', 'member'], true), 422);
        User::query()->where('company_id', $this->companyId)->findOrFail($userId)->update(['company_role' => $role]);
    }

    public function removeMember(int $userId): void
    {
        $this->authorizeManage();
        $user = User::query()->where('company_id', $this->companyId)->findOrFail($userId);
        $user->update(['company_id' => null, 'company_role' => null]);
        AuditLogger::log('company.member_removed', $this->company(), "{$user->email} removed from company.");
    }

    public function render()
    {
        $company = $this->company()->load(['users', 'addresses', 'customerGroup', 'salesRep']);

        return view('livewire.admin.company-show', [
            'company' => $company,
            'groups' => CustomerGroup::query()->orderBy('name')->pluck('name', 'id'),
            'staff' => User::staff()->orderBy('name')->pluck('name', 'id'),
            'prices' => CustomerPrice::query()->where('company_id', $company->id)->with(['product', 'variant'])->orderBy('product_id')->get(),
            'quotes' => $company->quotes()->latest('id')->limit(10)->get(),
            'canManage' => auth()->user()->can('customers.manage'),
        ])->layout('components.layouts.admin', ['title' => $company->name]);
    }
}
