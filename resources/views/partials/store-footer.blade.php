@php
    $footerCategories = \App\Models\Category::active()->roots()->orderBy('sort_order')->limit(6)->get();
    $hasSocial = (bool) \App\Support\Social::visible();
    $tel = preg_replace('/[^\d+]/', '', $brand['phone']);
@endphp
<footer class="band grain mt-0" aria-label="Site footer">
    {{-- Site-wide next step: every page ends with the quotation route, except focused flows
         (sign-in, the quote form itself) where it would only distract. --}}
    @php $focused = request()->routeIs('login', 'register', 'password.*', 'verification.*', 'quote.*', 'quotes.public.*', 'account.*'); @endphp
    @unless ($focused)
    <div class="container-page pt-16 sm:pt-20">
        <div class="stitch-frame flex flex-col gap-8 rounded-xl bg-band-2 px-6 py-10 sm:px-10 sm:py-12 lg:flex-row lg:items-center lg:justify-between" style="--stitch-color: rgb(217 154 99 / .45); --stitch-radius: 24px; --stitch-inset: 10px">
            <div class="max-w-2xl">
                <p class="eyebrow mb-4">Wholesale &amp; private label</p>
                <h2 id="footer-cta-heading" class="text-[2rem] leading-[1.08] sm:text-[2.75rem]">Let’s build your next collection together.</h2>
                <p class="mt-4 text-on-band/75">Tell us the products, quantities and branding you need. We reply with a formal quotation, usually within one business day.</p>
            </div>
            <div class="flex shrink-0 flex-col gap-3 sm:flex-row lg:flex-col xl:flex-row">
                <a href="{{ route('quote.basket') }}" class="btn btn-primary btn-lg">Request a wholesale quote <x-icon name="arrow-right" class="h-4 w-4"/></a>
                <a href="{{ route('contact') }}" class="btn btn-outline-light btn-lg">Talk to our team</a>
            </div>
        </div>
    </div>
    @endunless

    <div class="container-page grid gap-12 py-16 md:grid-cols-12">
        <div class="md:col-span-4">
            <x-logo size="h-12" tone="light"/>
            <p class="mt-5 max-w-xs text-sm leading-relaxed text-on-band/70">Leather bags, belts, wallets and accessories, made in Sialkot. Wholesale supply and private-label manufacturing for retailers, distributors and brands.</p>
            <ul class="mt-6 space-y-2.5 text-sm">
                <li class="flex items-center gap-2.5"><x-icon name="mail" class="h-4 w-4 text-hide"/><a class="footer-link" href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a></li>
                <li class="flex items-center gap-2.5"><x-icon name="phone" class="h-4 w-4 text-hide"/><a class="footer-link" href="tel:{{ $tel }}">{{ $brand['phone'] }}</a></li>
                <li class="flex items-start gap-2.5"><x-icon name="pin" class="mt-0.5 h-4 w-4 shrink-0 text-hide"/><span class="text-on-band/75">{{ $brand['address'] }}</span></li>
            </ul>
        </div>
        <nav class="grid gap-10 sm:grid-cols-3 md:col-span-8 lg:grid-cols-4" aria-label="Footer">
            <div>
                <h2 class="footer-heading">Collections</h2>
                <ul class="space-y-2.5 text-sm">
                    <li><a class="footer-link" href="{{ route('catalog') }}">All products</a></li>
                    @foreach ($footerCategories as $cat)
                        <li><a class="footer-link" href="{{ route('catalog.category', $cat) }}">{{ $cat->name }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div>
                <h2 class="footer-heading">Trade</h2>
                <ul class="space-y-2.5 text-sm">
                    <li><a class="footer-link" href="{{ route('quote.basket') }}">Request a quote</a></li>
                    <li><a class="footer-link" href="{{ route('register') }}">Open a trade account</a></li>
                    <li><a class="footer-link" href="{{ route('pages.show', 'custom-manufacturing') }}">Private label &amp; manufacturing</a></li>
                    <li><a class="footer-link" href="{{ route('pages.show', 'about') }}">About {{ $brand['name'] }}</a></li>
                    <li><a class="footer-link" href="{{ route('contact') }}">Contact us</a></li>
                </ul>
            </div>
            <div>
                <h2 class="footer-heading">Information</h2>
                <ul class="space-y-2.5 text-sm">
                    @foreach (\App\Http\Controllers\PageController::POLICIES as $slug => $label)
                        <li><a class="footer-link" href="{{ route('pages.show', $slug) }}">{{ $label }}</a></li>
                    @endforeach
                </ul>
            </div>
            <div class="sm:col-span-3 lg:col-span-1">
                @if (setting('newsletter.enabled'))
                    <h2 class="footer-heading">Trade newsletter</h2>
                    <p class="mb-3 text-sm text-on-band/70">New collections, restocks and production slots.</p>
                    @include('partials.newsletter-form', ['compact' => true])
                @endif
                @if ($hasSocial)
                    {{-- Social profiles: icon + name rows; the round icon badges set them apart from the text links. --}}
                    <h2 class="footer-heading {{ setting('newsletter.enabled') ? 'mt-8' : '' }}">Follow us</h2>
                    <x-social-links tone="dark" layout="list"/>
                @endif
            </div>
        </nav>
    </div>
    <div class="border-t border-on-band/10">
        <div class="container-page footer-bottom flex flex-wrap items-center justify-between gap-2 pb-24 pt-5 sm:pb-5 text-xs text-on-band/55">
            <p>© {{ now()->year }} {{ $brand['name'] }}. Prices exclude tax and shipping unless stated. Submitted quote requests are not orders.</p>
            <p>All prices in {{ config('store.currency') }}.</p>
        </div>
    </div>
</footer>
