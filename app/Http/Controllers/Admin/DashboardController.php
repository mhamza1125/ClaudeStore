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
            'contentGaps' => $user->can('settings.manage') ? $this->contentGaps() : [],
            'imageReview' => $user->can('products.manage') ? \App\Models\ProductImage::query()->where('status', 'pending_review')->count() : null,
        ]);
    }

    /**
     * Public-site content that is still missing, so administrators know what to supply.
     *
     * @return list<array{label: string, url: string}>
     */
    private function contentGaps(): array
    {
        $gaps = [];
        if (blank(setting('company.intro'))) {
            $gaps[] = ['label' => 'Company introduction (homepage and About page) — currently the default brand description', 'url' => route('admin.settings')];
        }
        if (blank(setting('company.story'))) {
            $gaps[] = ['label' => 'Company story: workshop, location and certifications (About page)', 'url' => route('admin.settings')];
        }
        if (! \App\Models\HeroSlide::query()->live()->whereNotNull('image_path')->exists()) {
            $gaps[] = ['label' => 'Homepage hero: upload a genuine banner photograph', 'url' => route('admin.hero-slides')];
        }
        $noImage = \App\Models\Category::active()->roots()->where('is_featured', true)->whereNull('image_path')->count();
        if ($noImage > 0) {
            $gaps[] = ['label' => $noImage.' featured '.\Illuminate\Support\Str::plural('category', $noImage).' without an image (shown on the homepage)', 'url' => route('admin.categories')];
        }
        $noPhoto = Product::published()->whereDoesntHave('approvedImages')->count();
        if ($noPhoto > 0) {
            $gaps[] = ['label' => $noPhoto.' published '.\Illuminate\Support\Str::plural('product', $noPhoto).' without an approved image', 'url' => route('admin.products.index')];
        }

        return $gaps;
    }
}
