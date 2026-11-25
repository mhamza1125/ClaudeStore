<?php

namespace App\Providers;

use App\Assistant\AssistantManager;
use App\Assistant\Contracts\ChatProvider;
use App\Images\Contracts\ImageProvider;
use App\Images\ImageProviderManager;
use App\Models\User;
use App\Pricing\PriceResolver;
use App\Services\QuoteBasketService;
use App\Services\Settings;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->singleton(PriceResolver::class);
        $this->app->singleton(ImageProvider::class, fn () => ImageProviderManager::make(config('store.images.provider')));
        $this->app->singleton(ChatProvider::class, fn () => AssistantManager::make(config('store.assistant.provider')));
    }

    public function boot(): void
    {
        Model::preventSilentlyDiscardingAttributes(! $this->app->isProduction());

        // Data protection: commands that drop tables or wipe data (migrate:fresh / refresh /
        // reset / rollback, db:wipe) refuse to run in every environment. The test suite uses its
        // own throwaway database; DB_ALLOW_DESTRUCTIVE_COMMANDS=true is a deliberate local override.
        DB::prohibitDestructiveCommands(! $this->app->runningUnitTests() && ! config('database.allow_destructive_commands'));

        // Trusted proxies from TRUSTED_PROXIES (audit F-18). Empty = trust none (Laravel's default).
        // Set here, not in bootstrap/app.php, because .env is not loaded when that runs.
        if (filled($proxies = config('security.trusted_proxies'))) {
            TrustProxies::at($proxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', (string) $proxies)))));
        }

        // Super-admins pass every permission and policy check.
        Gate::before(fn (User $user) => $user->hasRole('super-admin') ? true : null);

        RateLimiter::for('quote-submit', fn (Request $request) => [
            Limit::perHour((int) config('store.quote_rate_limit_per_hour', 5))->by('quote:'.$request->ip()),
            Limit::perMinute(2)->by('quote-burst:'.$request->ip()),
        ]);
        RateLimiter::for('newsletter', fn (Request $request) => Limit::perHour(5)->by('nl:'.$request->ip()));
        RateLimiter::for('contact', fn (Request $request) => [
            Limit::perHour(5)->by('contact:'.$request->ip()),
            Limit::perMinute(2)->by('contact-burst:'.$request->ip()),
        ]);
        RateLimiter::for('quote-response', fn (Request $request) => Limit::perMinute(10)->by('qr:'.$request->ip()));
        // Chatbot: each message is a paid AI call, so cap requests per IP per minute and per day.
        RateLimiter::for('assistant', fn (Request $request) => [
            Limit::perMinute((int) config('store.assistant.rate_per_minute', 8))->by('as-m:'.$request->ip()),
            Limit::perDay((int) config('store.assistant.rate_per_day', 80))->by('as-d:'.$request->ip()),
        ]);
        RateLimiter::for('search', fn (Request $request) => Limit::perMinute(90)->by('search:'.$request->ip()));
        // Product images: generous for browsing, but slows bulk scraping of the catalogue.
        RateLimiter::for('media', fn (Request $request) => Limit::perMinute(300)->by('media:'.$request->ip()));
        RateLimiter::for('sensitive', fn (Request $request) => Limit::perMinute(30)->by('s:'.($request->user()?->id ?: $request->ip())));

        Event::listen(JobProcessing::class, fn () => app(Settings::class)->forgetLoaded());

        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            app(QuoteBasketService::class)->mergeGuestBasketInto($event->user);
        });

        // Settings are memoised per request by the Settings singleton; no static cache here
        // so admin changes show immediately (and long-running workers stay correct).
        View::composer('*', function ($view) {
            $brand = [
                'name' => setting('brand.name'),
                'tagline' => setting('brand.tagline'),
                'logo' => setting('brand.logo_path') ? Storage::disk('public')->url(setting('brand.logo_path')) : null,
                'email' => setting('brand.email'),
                'phone' => setting('brand.phone'),
                'address' => setting('brand.address'),
            ];
            $view->with('brand', $brand);
        });
    }
}
