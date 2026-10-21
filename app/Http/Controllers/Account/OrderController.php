<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;

class OrderController extends Controller
{
    public function index()
    {
        return view('account.orders', ['orders' => $this->scope()->latest('id')->paginate(15)]);
    }

    public function show(SalesOrder $order)
    {
        abort_unless($this->scope()->whereKey($order->id)->exists(), 404);

        return view('account.order', ['order' => $order->load('items', 'quote')]);
    }

    private function scope()
    {
        $user = auth()->user();

        return SalesOrder::query()->where(function ($q) use ($user) {
            $q->where('user_id', $user->id);
            if ($user->company_id) {
                $q->orWhere('company_id', $user->company_id);
            }
        });
    }
}
