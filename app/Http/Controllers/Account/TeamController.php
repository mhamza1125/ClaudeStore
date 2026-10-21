<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;

/** Read-only list of the company's users. Invitations are a documented future feature. */
class TeamController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company;

        return view('account.team', ['company' => $company, 'members' => $company?->users()->orderBy('name')->get() ?? collect()]);
    }
}
