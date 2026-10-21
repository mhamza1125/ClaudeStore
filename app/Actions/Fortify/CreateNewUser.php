<?php

namespace App\Actions\Fortify;

use App\Models\Company;
use App\Models\CustomerGroup;
use App\Models\User;
use Illuminate\Support\Facades\DB;
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
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'company_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', Rule::in(array_keys(countries()))],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
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
            ]);
            $user->assignRole('customer');

            return $user;
        });
    }
}
