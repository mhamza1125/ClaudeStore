<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $default = $request->user()->can('admin.access') ? route('admin.dashboard') : route('account.dashboard');

        return redirect()->intended($default);
    }
}
