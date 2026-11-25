<?php

namespace App\Pricing;

use App\Models\User;

/**
 * Who may see catalogue prices, from Admin → B2B settings. See docs/B2B.md.
 *
 * With B2B pricing off, everyone may see prices and the existing rules apply unchanged
 * (public pricing flag, wholesale approval). Suspended accounts and individual
 * "never show prices" overrides are respected in both modes.
 */
class PriceAccess
{
    public const METHODS = [
        'instant' => 'Register and access',
        'verify' => 'Register and verify email',
        'approval' => 'Register and wait for approval',
        'verify_approval' => 'Register, verify and wait for approval',
    ];

    public function enabled(): bool
    {
        return (bool) setting('b2b.enabled');
    }

    public function current(): PriceAccessDecision
    {
        return $this->for(auth()->user());
    }

    /** The anonymous view (structured data, chatbot context). */
    public function guest(): PriceAccessDecision
    {
        return $this->for(null);
    }

    public function for(?User $user): PriceAccessDecision
    {
        $company = $user?->company;

        if ($user && $user->isStaff()) {
            return new PriceAccessDecision(true, PriceAccessDecision::OK, $company, tradeCatalogue: true);
        }
        // is_active is null on a freshly created model (database default applies): treat as active.
        if ($user && ($user->is_active === false || $user->price_access === 'deny')) {
            return PriceAccessDecision::denied(PriceAccessDecision::RESTRICTED);
        }
        if (! $this->enabled()) {
            return new PriceAccessDecision(true, PriceAccessDecision::OK, $company);
        }

        if (! $user) {
            return setting('b2b.guest_prices') && ! setting('b2b.require_registration')
                ? new PriceAccessDecision(true)
                : PriceAccessDecision::denied(PriceAccessDecision::GUEST);
        }

        if ($user->price_access !== 'allow' && setting('b2b.require_registration')) {
            if (setting('b2b.require_email_verification') && ! $user->hasVerifiedEmail()) {
                return PriceAccessDecision::denied(PriceAccessDecision::VERIFY);
            }
            if (setting('b2b.require_approval') && ! $user->isApproved()) {
                return PriceAccessDecision::denied($user->approval_status === 'rejected' ? PriceAccessDecision::REJECTED : PriceAccessDecision::APPROVAL);
            }
        }

        return new PriceAccessDecision(true, PriceAccessDecision::OK, $company, tradeCatalogue: true);
    }

    /** Key of METHODS for the current settings (how a new registrant gains access). */
    public function method(): string
    {
        $gated = (bool) setting('b2b.require_registration');
        $verify = $gated && setting('b2b.require_email_verification');
        $approval = $gated && setting('b2b.require_approval');

        return match (true) {
            $verify && $approval => 'verify_approval',
            $verify => 'verify',
            $approval => 'approval',
            default => 'instant',
        };
    }

    /** New registrations wait for review only while approval is required. */
    public function approvalRequired(): bool
    {
        return $this->enabled() && setting('b2b.require_registration') && setting('b2b.require_approval');
    }

    public function verificationRequired(): bool
    {
        return $this->enabled() && setting('b2b.require_registration') && setting('b2b.require_email_verification');
    }

    public function guestInquiriesAllowed(): bool
    {
        return ! $this->enabled() || (bool) setting('b2b.guest_inquiries');
    }
}
