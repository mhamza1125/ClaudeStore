<?php

namespace App\Actions\Fortify;

use App\Mail\NewCustomerRegistered;
use App\Models\Company;
use App\Models\CustomerGroup;
use App\Models\User;
use App\Pricing\PriceAccess;
use App\Rules\TrustedEmail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /** Registers a buyer. A company profile is created when a company name is given. */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email:rfc', 'max:255', new TrustedEmail, Rule::unique(User::class)],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', Rule::in(array_keys(countries()))],
            'password' => $this->passwordRules(),
        ])->validate();

        // Accounts wait for review only while B2B approval is required (Admin → B2B settings).
        $needsApproval = app(PriceAccess::class)->approvalRequired();

        $user = DB::transaction(function () use ($input, $needsApproval) {
            $company = null;
            if (filled($input['company_name'] ?? null)) {
                $company = Company::create([
                    'name' => $input['company_name'],
                    'email' => $input['email'],
                    'phone' => $input['phone'] ?? null,
                    'country' => $input['country'] ?? null,
                    'customer_group_id' => CustomerGroup::query()->where('is_default', true)->value('id'),
                ]);
            }

            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'phone' => $input['phone'] ?? null,
                'password' => $input['password'],
                'company_id' => $company?->id,
                'company_role' => $company ? 'owner' : null,
                'approval_status' => $needsApproval ? 'pending' : 'approved',
                'approved_at' => $needsApproval ? null : now(),
            ]);
            $user->assignRole('customer');

            return $user;
        });

        // New B2B lead: tell the sales team (who may need to approve it).
        if (app(PriceAccess::class)->enabled() && ($to = setting('sales.notification_email'))) {
            try {
                Mail::to($to)->queue(new NewCustomerRegistered($user));
            } catch (\Throwable $e) {
                Log::error('New customer alert could not be queued', ['user' => $user->id, 'error' => $e->getMessage()]);
            }
        }

        return $user;
    }
}
