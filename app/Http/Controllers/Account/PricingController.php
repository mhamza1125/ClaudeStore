<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\CustomerPrice;
use App\Models\PriceTier;

/** Shows only the signed-in company's own group and negotiated prices. */
class PricingController extends Controller
{
    public function index()
    {
        $company = auth()->user()->company?->load('customerGroup');
        $approved = $company?->isWholesaleApproved() ?? false;

        return view('account.pricing', [
            'company' => $company,
            'approved' => $approved,
            'negotiated' => $approved ? CustomerPrice::query()->where('company_id', $company->id)
                ->with(['product', 'variant'])->whereHas('product', fn ($q) => $q->published())
                ->where(fn ($q) => $q->whereNull('valid_until')->orWhereDate('valid_until', '>=', today()))
                ->orderBy('product_id')->get() : collect(),
            'groupTiers' => $approved && $company->customer_group_id ? PriceTier::query()
                ->where('customer_group_id', $company->customer_group_id)->with(['product', 'variant'])
                ->whereHas('product', fn ($q) => $q->published())->orderBy('product_id')->orderBy('min_qty')->get()
                ->groupBy('product_id') : collect(),
        ]);
    }
}
