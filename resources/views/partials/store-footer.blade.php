<footer class="mt-24 border-t border-border bg-elevated/60">
    <div class="container-page grid gap-10 py-14 md:grid-cols-12">
        <div class="md:col-span-4">
            <x-logo/>
            <p class="mt-4 max-w-xs text-sm text-muted">{{ $brand['tagline'] }}. Wholesale, distribution and private-label manufacturing for retailers and brands.</p>
            <ul class="mt-6 space-y-2 text-sm">
                <li class="flex items-center gap-2"><x-icon name="mail" class="h-4 w-4 text-accent"/><a class="link" href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a></li>
                <li class="flex items-center gap-2"><x-icon name="phone" class="h-4 w-4 text-accent"/><span>{{ $brand['phone'] }}</span></li>
                <li class="flex items-start gap-2"><x-icon name="pin" class="h-4 w-4 shrink-0 text-accent"/><span>{{ $brand['address'] }}</span></li>
            </ul>
        </div>
        <div class="md:col-span-2">
            <h2 class="eyebrow mb-4">Shop</h2>
            <ul class="space-y-2 text-sm">
                <li><a class="hover:text-accent" href="{{ route('catalog') }}">All products</a></li>
                @foreach (\App\Models\Category::active()->roots()->orderBy('sort_order')->limit(5)->get() as $cat)
                    <li><a class="hover:text-accent" href="{{ route('catalog.category', $cat) }}">{{ $cat->name }}</a></li>
                @endforeach
            </ul>
        </div>
        <div class="md:col-span-2">
            <h2 class="eyebrow mb-4">Trade</h2>
            <ul class="space-y-2 text-sm">
                <li><a class="hover:text-accent" href="{{ route('quote.basket') }}">Request a quote</a></li>
                <li><a class="hover:text-accent" href="{{ route('register') }}">Open a trade account</a></li>
                <li><a class="hover:text-accent" href="{{ route('pages.show', 'custom-manufacturing') }}">Custom &amp; private label</a></li>
                <li><a class="hover:text-accent" href="{{ route('pages.show', 'about') }}">About the workshop</a></li>
            </ul>
        </div>
        <div class="md:col-span-2">
            <h2 class="eyebrow mb-4">Policies</h2>
            <ul class="space-y-2 text-sm">
                @foreach (\App\Http\Controllers\PageController::POLICIES as $slug => $label)
                    <li><a class="hover:text-accent" href="{{ route('pages.show', $slug) }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
        <div class="md:col-span-2">
            @if (setting('newsletter.enabled'))
                <h2 class="eyebrow mb-4">Trade newsletter</h2>
                @include('partials.newsletter-form', ['compact' => true])
            @endif
            @php $socials = array_filter(['Instagram' => setting('social.instagram'), 'LinkedIn' => setting('social.linkedin'), 'Facebook' => setting('social.facebook')]); @endphp
            @if ($socials)
                <h2 class="eyebrow mb-4 mt-6">Follow</h2>
                <ul class="space-y-2 text-sm">
                    @foreach ($socials as $label => $url)<li><a class="hover:text-accent" href="{{ $url }}" rel="noopener" target="_blank">{{ $label }}</a></li>@endforeach
                </ul>
            @endif
        </div>
    </div>
    <div class="border-t border-border">
        <div class="container-page flex flex-wrap items-center justify-between gap-2 py-5 text-xs text-muted">
            <p>© {{ now()->year }} {{ $brand['name'] }}. Prices exclude tax and shipping unless stated. Submitted quote requests are not orders.</p>
            <p>All prices in {{ config('store.currency') }}.</p>
        </div>
    </div>
</footer>
