<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AddressController extends Controller
{
    public function store(Request $request)
    {
        $company = $request->user()->company;
        abort_unless($company, 403, 'Create your company profile first.');
        $data = $this->validated($request);
        $address = $company->addresses()->create($data);
        $this->syncDefault($address);

        return back()->with('status', 'Address added.');
    }

    public function update(Request $request, Address $address)
    {
        $this->authorizeAddress($request, $address);
        $address->update($this->validated($request));
        $this->syncDefault($address);

        return back()->with('status', 'Address updated.');
    }

    public function destroy(Request $request, Address $address)
    {
        $this->authorizeAddress($request, $address);
        $address->delete();

        return back()->with('status', 'Address removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', Rule::in(['billing', 'shipping'])],
            'label' => ['nullable', 'string', 'max:80'],
            'recipient' => ['nullable', 'string', 'max:120'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:20'],
            'country' => ['required', Rule::in(array_keys(countries()))],
            'phone' => ['nullable', 'string', 'max:40'],
            'is_default' => ['nullable', 'boolean'],
        ]) + ['is_default' => $request->boolean('is_default')];
    }

    private function syncDefault(Address $address): void
    {
        if ($address->is_default) {
            Address::query()->where('company_id', $address->company_id)->where('type', $address->type)
                ->whereKeyNot($address->id)->update(['is_default' => false]);
        }
    }

    private function authorizeAddress(Request $request, Address $address): void
    {
        abort_unless($request->user()->company_id && $address->company_id === $request->user()->company_id, 404);
    }
}
