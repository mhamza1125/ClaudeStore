<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SalesOrderStatus;
use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $orders = SalesOrder::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w->where('reference', 'like', like_term($request->string('q')))->orWhere('company_name', 'like', like_term($request->string('q')))))
            ->latest('id')->paginate(25)->withQueryString();

        return view('admin.orders.index', ['orders' => $orders, 'statuses' => SalesOrderStatus::options()]);
    }

    public function show(SalesOrder $order)
    {
        $order->load(['items', 'quote']);
        // Sales reps may view orders for quotes they can see.
        abort_unless(auth()->user()->can('orders.manage') || auth()->user()->can('view', $order->quote), 403);

        return view('admin.orders.show', ['order' => $order, 'statuses' => SalesOrderStatus::options()]);
    }

    public function update(Request $request, SalesOrder $order)
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(SalesOrderStatus::class)],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ]);
        $order->fill($data);
        $changes = AuditLogger::diff($order);
        $order->save();
        if ($changes) {
            AuditLogger::log('order.updated', $order, "Order {$order->reference} updated.", $changes);
        }

        return back()->with('status', 'Order updated.');
    }
}
