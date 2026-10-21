<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Mail\WholesaleApplicationReceived;
use App\Models\Company;
use App\Models\CustomerGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class CompanyController extends Controller
{
    public function edit()
    {
        $user = auth()->user()->load('company.addresses');

        return view('account.company', ['user' => $user, 'company' => $user->company]);
    }

    public function update(Request $request)
    {
        $user = $request->user();
        if ($user->company && ! $user->isCompanyOwner()) {
            abort(403, 'Only the company owner can edit company details.');
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'country' => ['required', Rule::in(array_keys(countries()))],
            'vat_number' => ['nullable', 'string', 'max:60'],
            'business_type' => ['nullable', 'string', 'max:60'],
        ]);

        DB::transaction(function () use ($user, $data) {
            if ($user->company) {
                $user->company->update($data);
            } else {
                $company = Company::create($data + ['customer_group_id' => CustomerGroup::query()->where('is_default', true)->value('id')]);
                $user->update(['company_id' => $company->id, 'company_role' => 'owner']);
                $user->setRelation('company', $company);
            }
        });

        return redirect()->route('account.company')->with('status', 'Company profile saved.');
    }

    public function requestWholesale(Request $request)
    {
        $user = $request->user();
        $company = $user->company;
        abort_unless($company && $user->isCompanyOwner(), 403);
        if (in_array($company->wholesale_status, ['pending', 'approved'], true)) {
            return back()->with('warning', 'Your wholesale application is already '.$company->wholesale_status.'.');
        }
        $data = $request->validate(['wholesale_application' => ['required', 'string', 'min:20', 'max:3000']], [
            'wholesale_application.min' => 'Please tell us a little more about your business (at least 20 characters).',
        ]);
        $company->update($data + ['wholesale_status' => 'pending', 'wholesale_requested_at' => now()]);

        if ($to = setting('sales.notification_email')) {
            Mail::to($to)->queue(new WholesaleApplicationReceived($company));
        }

        return back()->with('status', 'Thank you. Our trade team will review your application.');
    }
}
