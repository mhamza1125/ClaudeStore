<?php

use App\Http\Controllers\Account;
use App\Http\Controllers\Admin;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\CustomerQuoteController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImageController;
use App\Http\Controllers\QuoteSubmissionController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\MarkAdminSectionSeen;
use App\Images\ProtectedImages;
use App\Livewire;
use App\Services\AdminActivity;
use Illuminate\Support\Facades\Route;

/*
| Storefront
*/
Route::get('/', HomeController::class)->name('home');
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/products', Livewire\Store\Catalog::class)->name('catalog');
Route::get('/categories/{category:slug}', Livewire\Store\Catalog::class)->name('catalog.category');
Route::get('/products/{product:slug}', [ProductController::class, 'show'])->name('products.show')
    // A former slug: 301 to the current URL (audit F-11).
    ->missing(fn (\Illuminate\Http\Request $request) => app(ProductController::class)->legacySlug((string) $request->route()->originalParameter('product')));
Route::get('/search/suggest', [SearchController::class, 'suggest'])->middleware('throttle:search')->name('search.suggest');
Route::get('/products/{product:slug}/spec-sheet', [ProductController::class, 'specSheet'])->name('products.spec-sheet');
// Watermarked product image renditions (signed URLs; originals are never public).
Route::get('/media/products/{image}/{size}/{signature}', [ProductImageController::class, 'show'])
    ->whereIn('size', array_keys(ProtectedImages::SIZES))->middleware('throttle:media')->name('media.product-image');
Route::post('/assistant/messages', [AssistantController::class, 'send'])->middleware('throttle:assistant')->name('assistant.send');
Route::post('/assistant/reset', [AssistantController::class, 'reset'])->name('assistant.reset');
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:newsletter')->name('newsletter.subscribe');
Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->middleware('throttle:contact')->name('contact.store');

// Quote basket and RFQ submission (guests allowed; rate limited + honeypot)
Route::get('/quote', Livewire\Store\QuoteBasketPage::class)->name('quote.basket');
Route::post('/quote', [QuoteSubmissionController::class, 'store'])->middleware('throttle:quote-submit')->name('quote.submit');
Route::get('/quote/submitted', [QuoteSubmissionController::class, 'confirmation'])->name('quote.confirmation');

// Secure customer quotation link (token-hashed; works without an account)
Route::middleware('throttle:quote-response')->group(function () {
    Route::get('/q/{quote:reference}/{token}', [CustomerQuoteController::class, 'show'])->name('quotes.public.show');
    Route::get('/q/{quote:reference}/{token}/pdf', [CustomerQuoteController::class, 'pdf'])->name('quotes.public.pdf');
    Route::post('/q/{quote:reference}/{token}/accept', [CustomerQuoteController::class, 'accept'])->name('quotes.public.accept');
    Route::post('/q/{quote:reference}/{token}/reject', [CustomerQuoteController::class, 'reject'])->name('quotes.public.reject');
});

// Signing out is POST-only (Fortify); opening /logout directly just goes home.
Route::get('/logout', fn () => redirect()->route('home'));

Route::middleware('auth')->group(function () {
    Route::get('/attachments/{attachment}', [AttachmentController::class, 'show'])->name('attachments.show');
});

/*
| Informational pages at the site root (/about, /custom-manufacturing, /terms …).
| Constrained to the known slugs, so they cannot shadow products, categories or any
| other route. Old /pages/{slug} URLs redirect permanently.
*/
Route::get('/pages/{page}', [PageController::class, 'legacy'])->whereIn('page', PageController::slugs());
Route::get('/{page}', [PageController::class, 'show'])->whereIn('page', PageController::slugs())->name('pages.show');

/*
| Customer portal
*/
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [Account\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [Account\ProfileController::class, 'edit'])->name('profile');
    Route::get('/company', [Account\CompanyController::class, 'edit'])->name('company');
    Route::put('/company', [Account\CompanyController::class, 'update'])->name('company.update');
    Route::post('/company/wholesale', [Account\CompanyController::class, 'requestWholesale'])->middleware('throttle:sensitive')->name('company.wholesale');
    Route::post('/addresses', [Account\AddressController::class, 'store'])->name('addresses.store');
    Route::put('/addresses/{address}', [Account\AddressController::class, 'update'])->name('addresses.update');
    Route::delete('/addresses/{address}', [Account\AddressController::class, 'destroy'])->name('addresses.destroy');
    Route::get('/pricing', [Account\PricingController::class, 'index'])->name('pricing');
    Route::get('/quotes', [Account\QuoteController::class, 'index'])->name('quotes.index');
    Route::get('/quotes/{quote:reference}', [Account\QuoteController::class, 'show'])->name('quotes.show');
    Route::get('/quotes/{quote:reference}/pdf', [Account\QuoteController::class, 'pdf'])->name('quotes.pdf');
    Route::post('/quotes/{quote:reference}/accept', [Account\QuoteController::class, 'accept'])->middleware('throttle:sensitive')->name('quotes.accept');
    Route::post('/quotes/{quote:reference}/reject', [Account\QuoteController::class, 'reject'])->middleware('throttle:sensitive')->name('quotes.reject');
    Route::post('/quotes/{quote:reference}/reorder', [Account\ReorderController::class, 'quote'])->name('quotes.reorder');
    Route::get('/orders', [Account\OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/{order:reference}', [Account\OrderController::class, 'show'])->name('orders.show');
    Route::post('/orders/{order:reference}/reorder', [Account\ReorderController::class, 'order'])->name('orders.reorder');
    Route::get('/favorites', [Account\FavoriteController::class, 'index'])->name('favorites');
    Route::post('/favorites/{product}', [Account\FavoriteController::class, 'store'])->name('favorites.store');
    Route::delete('/favorites/{product}', [Account\FavoriteController::class, 'destroy'])->name('favorites.destroy');
    Route::get('/team', [Account\TeamController::class, 'index'])->name('team');
});

/*
| Admin (custom Livewire admin; no Filament)
*/
Route::middleware(['auth', 'can:admin.access', MarkAdminSectionSeen::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');
    // Navigation badge counts, polled by the admin layout.
    Route::get('/activity', fn (AdminActivity $activity) => response()->json(['counts' => $activity->counts(request()->user())]))
        ->middleware('throttle:sensitive')->name('activity');

    Route::get('/quotes', Livewire\Admin\QuoteIndex::class)->name('quotes.index');
    Route::get('/quotes/{quote:reference}', Livewire\Admin\QuoteWorkspace::class)->name('quotes.show');
    Route::get('/quotes/{quote:reference}/pdf', [Admin\QuotePdfController::class, 'show'])->name('quotes.pdf');

    Route::get('/orders', [Admin\OrderController::class, 'index'])->middleware('can:orders.manage')->name('orders.index');
    Route::get('/orders/{order:reference}', [Admin\OrderController::class, 'show'])->name('orders.show');
    Route::put('/orders/{order:reference}', [Admin\OrderController::class, 'update'])->middleware('can:orders.manage')->name('orders.update');

    Route::middleware('can:products.manage')->group(function () {
        Route::get('/products', Livewire\Admin\ProductIndex::class)->name('products.index');
        Route::get('/products/create', Livewire\Admin\ProductEditor::class)->name('products.create');
        Route::get('/products/{product:id}/edit', Livewire\Admin\ProductEditor::class)->name('products.edit');
        Route::get('/categories', Livewire\Admin\CategoryManager::class)->name('categories');
        Route::get('/import-export', [Admin\ProductCsvController::class, 'index'])->name('csv.index');
        Route::get('/products-export.csv', [Admin\ProductCsvController::class, 'export'])->name('csv.export');
        Route::post('/products-import', [Admin\ProductCsvController::class, 'import'])->name('csv.import');
    });

    Route::middleware('can:customers.view')->group(function () {
        Route::get('/customers', Livewire\Admin\CompanyIndex::class)->name('companies.index');
        Route::get('/customers/{company}', Livewire\Admin\CompanyShow::class)->name('companies.show');
    });
    Route::get('/customer-groups', Livewire\Admin\CustomerGroupManager::class)->middleware('can:customers.manage')->name('groups');
    Route::get('/customer-accounts', Livewire\Admin\CustomerAccounts::class)->middleware('can:customers.view')->name('customer-accounts');
    Route::get('/messages', Livewire\Admin\ContactMessages::class)->middleware('can:customers.view')->name('messages');
    Route::get('/b2b-settings', Livewire\Admin\B2bSettings::class)->middleware('can:settings.manage')->name('b2b');

    Route::get('/homepage-slides', Livewire\Admin\HeroSlideManager::class)->middleware('can:settings.manage')->name('hero-slides');
    Route::get('/settings', Livewire\Admin\SettingsForm::class)->middleware('can:settings.manage')->name('settings');
    Route::get('/staff', Livewire\Admin\StaffManager::class)->middleware('can:staff.manage')->name('staff');
    Route::get('/audit-log', [Admin\AuditLogController::class, 'index'])->middleware('can:audit.view')->name('audit');
});
