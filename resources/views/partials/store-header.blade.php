@php
    $navCategories = \Illuminate\Support\Facades\Cache::remember('nav.categories', 600, fn () =>
        \App\Models\Category::active()->roots()->orderBy('sort_order')->with(['children' => fn ($q) => $q->where('is_active', true)])->get());
@endphp
<header x-data="{ mobile: false }" x-effect="$dispatch('mobile-nav', { open: mobile })" @keydown.escape.window="mobile = false" class="sticky top-0 z-(--z-header) border-b border-border bg-bg">
    <div class="hidden border-b border-border bg-band text-on-band sm:block">
        <div class="container-page flex h-9 items-center justify-between text-xs tracking-wide">
            <p class="hidden truncate md:block">Premium leather goods · Wholesale supply · Private-label manufacturing</p>
            <div class="flex items-center gap-5" data-testid="contact-ribbon">
                <a href="tel:{{ preg_replace('/[^\d+]/', '', $brand['phone']) }}" class="inline-flex items-center gap-1.5 hover:underline"><x-icon name="phone" class="h-3.5 w-3.5"/>{{ $brand['phone'] }}</a>
                <a href="mailto:{{ $brand['email'] }}" class="inline-flex items-center gap-1.5 hover:underline"><x-icon name="mail" class="h-3.5 w-3.5"/>{{ $brand['email'] }}</a>
                <a href="{{ route('contact') }}" class="hover:underline">Contact us</a>
            </div>
        </div>
    </div>
    <div class="container-page flex h-18 items-center justify-between gap-4 py-3">
        <div class="flex shrink-0 items-center gap-3">
            <button type="button" class="btn btn-ghost btn-sm !px-2 lg:hidden" @click="mobile = !mobile" :aria-expanded="mobile" aria-controls="mobile-nav" aria-label="Menu">
                <x-icon name="menu"/>
            </button>
            <x-logo/>
        </div>

        <nav class="hidden items-center gap-5 text-sm lg:flex xl:gap-8" aria-label="Main">
            @foreach ($navCategories as $cat)
                @php $active = request()->is('categories/'.$cat->slug); $hasKids = $cat->children->isNotEmpty(); $img = $cat->imageUrl(); @endphp
                <div class="group relative">
                    <a href="{{ route('catalog.category', $cat) }}" class="flex items-center gap-1 whitespace-nowrap border-b-2 py-2 transition-colors hover:text-accent {{ $active ? 'border-brass text-accent' : 'border-transparent' }}" @if ($hasKids) aria-haspopup="true" @endif>
                        {{ config('store.nav_labels.'.$cat->slug, $cat->name) }}@if ($hasKids)<x-icon name="chevron-down" class="h-3.5 w-3.5 transition-transform group-hover:rotate-180 group-focus-within:rotate-180"/>@endif
                    </a>
                    @if ($hasKids)
                        {{-- Mega menu: opens on hover and on keyboard focus (pure CSS, so it works without JS). --}}
                        <div class="invisible absolute top-full z-50 pt-3 opacity-0 transition duration-150 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 {{ $loop->index >= 3 ? 'right-0' : 'left-0' }}">
                            <div class="card flex overflow-hidden shadow-lg {{ $img ? 'w-[30rem]' : 'w-72' }}">
                                <div class="flex-1 p-5">
                                    <p class="eyebrow mb-3">{{ config('store.nav_labels.'.$cat->slug, $cat->name) }}</p>
                                    <ul class="space-y-0.5">
                                        @foreach ($cat->children as $child)
                                            <li><a href="{{ route('catalog.category', $child) }}" class="block py-1.5 transition-colors hover:text-accent">{{ $child->name }}</a></li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ route('catalog.category', $cat) }}" class="link mt-4 inline-flex items-center gap-1.5 text-xs font-medium uppercase tracking-wider">View all {{ config('store.nav_labels.'.$cat->slug, $cat->name) }} <x-icon name="arrow-right" class="h-3.5 w-3.5"/></a>
                                </div>
                                @if ($img)
                                    <a href="{{ route('catalog.category', $cat) }}" tabindex="-1" aria-hidden="true" class="block w-44 shrink-0 bg-elevated"><img src="{{ $img }}" alt="" loading="lazy" class="h-full w-full object-cover"></a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
        </nav>

        <div class="flex items-center gap-1">
            <x-search-box id="header-search" class="hidden md:block" input-class="w-44 lg:w-36 xl:w-64" dropdown-class="right-0 w-[min(28rem,calc(100vw-2rem))]"/>
            <x-theme-toggle class="hidden sm:block"/>
            @auth
                <div x-data="{ open: false }" class="relative" @click.outside="open = false">
                    <button type="button" class="btn btn-ghost btn-sm !px-2" @click="open = !open" :aria-expanded="open" aria-label="Account menu"><x-icon name="user"/></button>
                    <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 z-50 mt-2 w-56 card py-1 text-sm shadow-lg">
                        <p class="truncate px-4 py-2 text-xs text-muted">{{ auth()->user()->email }}</p>
                        @if (auth()->user()->can('admin.access'))
                            <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 hover:bg-elevated">Admin panel</a>
                        @endif
                        <a href="{{ route('account.dashboard') }}" class="block px-4 py-2 hover:bg-elevated">My account</a>
                        <a href="{{ route('account.quotes.index') }}" class="block px-4 py-2 hover:bg-elevated">My quotes</a>
                        <a href="{{ route('account.favorites') }}" class="block px-4 py-2 hover:bg-elevated">Saved products</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="block w-full px-4 py-2 text-left hover:bg-elevated">Sign out</button></form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm !px-2" aria-label="Sign in" title="Sign in" data-testid="sign-in"><x-icon name="user"/></a>
            @endauth
            <livewire:quote-basket-count/>
        </div>
    </div>

    {{-- Scrolls inside itself: content below a sticky header could otherwise never be reached. --}}
    <nav id="mobile-nav" x-show="mobile" x-cloak x-transition class="max-h-[calc(100dvh-4.5rem)] overflow-y-auto overscroll-contain border-t border-border bg-surface lg:hidden" aria-label="Mobile">
        <div class="container-page py-4">
            {{-- Search lives here below md; the theme switch moves here below sm. --}}
            <div class="mb-3 flex items-center gap-2 md:hidden">
                <x-search-box id="mobile-search" class="flex-1" dropdown-class="left-0 right-0"/>
                <x-theme-toggle class="sm:hidden"/>
            </div>
            <ul class="divide-y divide-border">
                <li><a href="{{ route('catalog') }}" class="block py-3 font-medium">All products</a></li>
                @foreach ($navCategories as $cat)
                    <li x-data="{ sub: false }">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('catalog.category', $cat) }}" class="flex-1 py-3">{{ config('store.nav_labels.'.$cat->slug, $cat->name) }}</a>
                            @if ($cat->children->isNotEmpty())
                                <button type="button" class="p-3 text-muted" @click="sub = !sub" :aria-expanded="sub" aria-label="Show {{ config('store.nav_labels.'.$cat->slug, $cat->name) }} subcategories"><x-icon name="chevron-down" class="h-4 w-4 transition-transform" x-bind:class="sub && 'rotate-180'"/></button>
                            @endif
                        </div>
                        @if ($cat->children->isNotEmpty())
                            <ul x-show="sub" x-cloak x-transition class="mb-3 ml-1 space-y-0.5 border-l border-border-strong pl-4">
                                @foreach ($cat->children as $child)
                                    <li><a href="{{ route('catalog.category', $child) }}" class="block py-1.5 text-sm text-muted hover:text-text">{{ $child->name }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
                <li><a href="{{ route('pages.show', 'custom-manufacturing') }}" class="block py-3">Private label &amp; manufacturing</a></li>
                {{-- The contact ribbon is hidden on phones, so the menu keeps the link. --}}
                <li class="sm:hidden"><a href="{{ route('contact') }}" class="block py-3 font-medium">Contact us</a></li>
            </ul>
            @guest
                <div class="flex gap-2 pt-4"><a href="{{ route('login') }}" class="btn btn-secondary flex-1">Sign in</a><a href="{{ route('register') }}" class="btn btn-primary flex-1">Open trade account</a></div>
            @endguest
        </div>
    </nav>
</header>
