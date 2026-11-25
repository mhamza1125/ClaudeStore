<?php

namespace App\Http\Responses;

use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;

/**
 * Audit F-17: a reset request for an unknown (or throttled) address gets the same answer as a
 * successful one, so the form cannot be used to discover which emails have accounts.
 */
class GenericPasswordResetLinkResponse implements FailedPasswordResetLinkRequestResponse
{
    public function __construct(protected string $status = '') {}

    public function toResponse($request)
    {
        return (new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT))->toResponse($request);
    }
}
