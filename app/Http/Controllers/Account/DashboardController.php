<?php

namespace App\Http\Controllers\Account;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Quote;
use App\Models\SalesOrder;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user()->load('company.customerGroup');
        $quotes = Quote::query()->visibleTo($user);

        return view('account.dashboard', [
            'user' => $user,
            'recent' => (clone $quotes)->latest('id')->limit(5)->get(),
            'awaiting' => (clone $quotes)->where('status', QuoteStatus::AwaitingResponse)->count(),
            'orders' => $user->company_id ? SalesOrder::query()->where('company_id', $user->company_id)->count()
                : SalesOrder::query()->where('user_id', $user->id)->count(),
        ]);
    }
}
