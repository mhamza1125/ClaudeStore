<?php

namespace App\Policies;

use App\Models\Quote;
use App\Models\User;

class QuotePolicy
{
    /** Staff: all quotes with quotes.view-all, otherwise assigned/unassigned. Customers: own or company quotes. */
    public function view(User $user, Quote $quote): bool
    {
        if ($user->isStaff()) {
            return $this->staffCanSee($user, $quote);
        }

        return $this->belongsToCustomer($user, $quote);
    }

    public function update(User $user, Quote $quote): bool
    {
        return $user->can('quotes.manage') && $this->staffCanSee($user, $quote);
    }

    public function assign(User $user, Quote $quote): bool
    {
        return $user->can('quotes.assign');
    }

    public function convert(User $user, Quote $quote): bool
    {
        return $user->can('quotes.convert') && $this->staffCanSee($user, $quote);
    }

    /** The submitting buyer or the company owner may accept/reject. */
    public function respond(User $user, Quote $quote): bool
    {
        return $quote->user_id === $user->id
            || ($quote->company_id && $quote->company_id === $user->company_id && $user->isCompanyOwner());
    }

    private function staffCanSee(User $user, Quote $quote): bool
    {
        if (! $user->can('admin.access')) {
            return false;
        }

        return $user->can('quotes.view-all') || $quote->assigned_to === null || $quote->assigned_to === $user->id;
    }

    private function belongsToCustomer(User $user, Quote $quote): bool
    {
        return $quote->user_id === $user->id || ($quote->company_id !== null && $quote->company_id === $user->company_id);
    }
}
