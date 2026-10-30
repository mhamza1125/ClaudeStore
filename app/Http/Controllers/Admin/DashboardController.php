<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Product;
use App\Models\Quote;
use App\Models\SalesOrder;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $quotes = Quote::query()->visibleTo($user);

        return view('admin.dashboard', [
            'counts' => [
                'new' => (clone $quotes)->where('status', QuoteStatus::Pending)->count(),
                'review' => (clone $quotes)->whereIn('status', [QuoteStatus::UnderReview, QuoteStatus::Quoted])->count(),
                'awaiting' => (clone $quotes)->where('status', QuoteStatus::AwaitingResponse)->count(),
                'accepted' => (clone $quotes)->where('status', QuoteStatus::Accepted)->count(),
            ],
            'recent' => (clone $quotes)->with('assignee')->latest('id')->limit(8)->get(),
            'pendingWholesale' => $user->can('customers.view') ? Company::query()->where('wholesale_status', 'pending')->count() : null,
            'pendingAccounts' => $user->can('customers.view') ? \App\Models\User::query()->role('customer')->where('approval_status', 'pending')->count() : 0,
            'orders' => $user->can('orders.manage') ? SalesOrder::query()->latest('id')->limit(5)->get() : collect(),
            'products' => $user->can('products.manage') ? ['published' => Product::published()->count(), 'draft' => Product::query()->where('status', 'draft')->count()] : null,
            'imageReview' => $user->can('products.manage') ? \App\Models\ProductImage::query()->where('status', 'pending_review')->count() : null,
        ]);
    }
}
