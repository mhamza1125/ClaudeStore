<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;

/** Profile and password forms post to Fortify's update endpoints. */
class ProfileController extends Controller
{
    public function edit()
    {
        return view('account.profile', ['user' => auth()->user()]);
    }
}
