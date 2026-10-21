<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()->with('user')
            ->when($request->filled('action'), fn ($q) => $q->where('action', 'like', $request->string('action').'%'))
            ->latest('id')->paginate(50)->withQueryString();

        return view('admin.audit', compact('logs'));
    }
}
