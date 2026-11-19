@php $footerCategories = \App\Models\Category::active()->roots()->orderBy('sort_order')->limit(6)->get(); @endphp
<footer class="mt-0 bg-band text-on-band">
    <div class="container-page grid gap-12 py-16 md:grid-cols-12">
        <div class="md:col-span-4">
            <x-logo size="h-14"/>
            <p class="mt-5 max-w-xs text-sm leading-relaxed text-on-band/75">Premium leather bags, belts, wallets and accessories, made in Sialkot. Wholesale supply and private-label manufacturing for retailers and brands.</p>
            <ul class="mt-6 space-y-2.5 text-sm">
                <li class="flex items-center gap-2.5"><x-icon name="mail" class="h-4 w-4 text-brass"/><a class="hover:underline" href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a></li>
                <li class="flex items-center gap-2.5"><x-icon name="phone" class="h-4 w-4 text-brass"/><a class="hover:underline" href="tel:{{ preg_replace('/[^\d+]/', '', $brand['phone']) }}">{{ $brand['phone'] }}</a></li>
                <li class="flex items-start gap-2.5"><x-icon name="pin" class="mt-0.5 h-4 w-4 shrink-0 text-brass"/><span>{{ $brand['address'] }}</span></li>
            </ul>
        </div>
        <div class="md:col-span-2">
            <h2 class="mb-4 font-sans text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-brass">Collections</h2>
            <ul class="space-y-2.5 text-sm">
                <li><a class="transition-colors hover:text-brass" href="{{ route('catalog') }}">All products</a></li>
                @foreach ($footerCategories as $cat)
                    <li><a class="transition-colors hover:text-brass" href="{{ route('catalog.category', $cat) }}">{{ $cat->name }}</a></li>
                @endforeach
            </ul>
        </div>
        <div class="md:col-span-2">
            <h2 class="mb-4 font-sans text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-brass">Trade</h2>
            <ul class="space-y-2.5 text-sm">
                <li><a class="transition-colors hover:text-brass" href="{{ route('quote.basket') }}">Request a quote</a></li>
                <li><a class="transition-colors hover:text-brass" href="{{ route('register') }}">Open a trade account</a></li>
                <li><a class="transition-colors hover:text-brass" href="{{ route('pages.show', 'custom-manufacturing') }}">Private label &amp; manufacturing</a></li>
                <li><a class="transition-colors hover:text-brass" href="{{ route('pages.show', 'about') }}">About {{ $brand['name'] }}</a></li>
                <li><a class="transition-colors hover:text-brass" href="{{ route('contact') }}">Contact us</a></li>
            </ul>
        </div>
        <div class="md:col-span-2">
            <h2 class="mb-4 font-sans text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-brass">Information</h2>
            <ul class="space-y-2.5 text-sm">
                @foreach (\App\Http\Controllers\PageController::POLICIES as $slug => $label)
                    <li><a class="transition-colors hover:text-brass" href="{{ route('pages.show', $slug) }}">{{ $label }}</a></li>
                @endforeach
            </ul>
        </div>
        <div class="md:col-span-2">
            @if (setting('newsletter.enabled'))
                <h2 class="mb-4 font-sans text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-brass">Trade newsletter</h2>
                @include('partials.newsletter-form', ['compact' => true])
            @endif
            @php $socials = array_filter(['Instagram' => setting('social.instagram'), 'LinkedIn' => setting('social.linkedin'), 'Facebook' => setting('social.facebook')]); @endphp
            @if ($socials)
                <h2 class="mb-4 mt-8 font-sans text-[0.6875rem] font-semibold uppercase tracking-[0.2em] text-brass">Follow</h2>
                <ul class="space-y-2.5 text-sm">
                    @foreach ($socials as $label => $url)<li><a class="transition-colors hover:text-brass" href="{{ $url }}" rel="noopener" target="_blank">{{ $label }}</a></li>@endforeach
                </ul>
            @endif
        </div>
    </div>
    <div class="border-t border-on-band/15">
        <div class="container-page footer-bottom flex flex-wrap items-center justify-between gap-2 py-5 text-xs text-on-band/65">
            <p>© {{ now()->year }} {{ $brand['name'] }}. Prices exclude tax and shipping unless stated. Submitted quote requests are not orders.</p>
            <p>All prices in {{ config('store.currency') }}.</p>
        </div>
    </div>
</footer>
