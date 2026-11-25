@php
    $navCategories = \Illuminate\Support\Facades\Cache::remember('nav.categories', 600, fn () =>
        \App\Models\Category::active()->roots()->orderBy('sort_order')->with(['children' => fn ($q) => $q->where('is_active', true)])->get());
    $navLabel = fn ($cat) => config('store.nav_labels.'.$cat->slug, $cat->name);
    $tel = preg_replace('/[^\d+]/', '', $brand['phone']);
@endphp
<header x-data="{ mobile: false, scrolled: window.scrollY > 8 }" x-effect="$dispatch('mobile-nav', { open: mobile })"
        @scroll.window.throttle.100ms="scrolled = window.scrollY > 8" @keydown.escape.window="mobile = false"
        :class="(scrolled || mobile) && 'is-scrolled'" class="site-header">
    {{-- Utility bar: who we are and how to reach us. --}}
    <div class="hidden bg-band text-on-band sm:block">
        <div class="container-page flex h-9 items-center justify-between gap-6 text-[12.5px]">
            <p class="hidden items-center gap-2 truncate text-on-band/80 md:flex">
                <span class="dot bg-thread" aria-hidden="true"></span>
                Leather goods manufacturer in Sialkot, Pakistan · Wholesale supply · Private label
            </p>
            <div class="flex items-center gap-5 text-on-band/85" data-testid="contact-ribbon">
                <a href="tel:{{ $tel }}" class="inline-flex items-center gap-1.5 hover:text-on-band"><x-icon name="phone" class="h-3.5 w-3.5 text-hide"/>{{ $brand['phone'] }}</a>
                <a href="mailto:{{ $brand['email'] }}" class="inline-flex items-center gap-1.5 hover:text-on-band"><x-icon name="mail" class="h-3.5 w-3.5 text-hide"/>{{ $brand['email'] }}</a>
                <a href="{{ route('contact') }}" class="font-medium hover:text-on-band">Contact us</a>
            </div>
        </div>
    </div>

    <div class="container-page flex h-[4.5rem] items-center justify-between gap-4">
        <div class="flex shrink-0 items-center gap-2">
            <button type="button" class="btn btn-ghost btn-icon -ml-2 lg:hidden" @click="mobile = !mobile" :aria-expanded="mobile" aria-controls="mobile-nav" aria-label="Menu">
                <x-icon name="menu" x-show="!mobile"/><x-icon name="x" x-show="mobile" x-cloak/>
            </button>
            <x-logo size="h-10 sm:h-11"/>
        </div>

        <nav class="hidden items-center gap-6 lg:flex xl:gap-8" aria-label="Main">
            @foreach ($navCategories as $cat)
                @php $active = request()->is('categories/'.$cat->slug) || $cat->children->contains(fn ($c) => request()->is('categories/'.$c->slug)); $hasKids = $cat->children->isNotEmpty(); $img = $cat->imageUrl(); @endphp
                <div class="group relative">
                    <a href="{{ route('catalog.category', $cat) }}" @class(['nav-link', 'is-active' => $active]) @if ($hasKids) aria-haspopup="true" @endif @if ($active) aria-current="true" @endif>
                        {{ $navLabel($cat) }}@if ($hasKids)<x-icon name="chevron-down" class="h-3.5 w-3.5 text-muted transition-transform duration-200 group-hover:rotate-180 group-focus-within:rotate-180"/>@endif
                    </a>
                    @if ($hasKids)
                        {{-- Mega menu: opens on hover and keyboard focus (pure CSS, works without JS). --}}
                        <div class="invisible absolute top-full z-50 pt-4 opacity-0 transition duration-200 group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100 {{ $loop->index >= 2 ? 'right-0' : 'left-0' }}">
                            <div class="card flex overflow-hidden shadow-lg {{ $img ? 'w-[34rem]' : 'w-72' }}">
                                <div class="flex-1 p-6">
                                    <p class="eyebrow mb-4">{{ $navLabel($cat) }}</p>
                                    <ul class="space-y-1">
                                        @foreach ($cat->children as $child)
                                            <li><a href="{{ route('catalog.category', $child) }}" class="-mx-2 flex items-center justify-between rounded-md px-2 py-2 text-sm transition-colors hover:bg-elevated hover:text-accent">{{ $child->name }}<x-icon name="chevron-right" class="h-3.5 w-3.5 text-border-strong"/></a></li>
                                        @endforeach
                                    </ul>
                                    <a href="{{ route('catalog.category', $cat) }}" class="link-arrow mt-5">All {{ strtolower($navLabel($cat)) }} <x-icon name="arrow-right" class="h-4 w-4"/></a>
                                </div>
                                @if ($img)
                                    <a href="{{ route('catalog.category', $cat) }}" tabindex="-1" aria-hidden="true" class="relative block w-52 shrink-0 bg-elevated">
                                        <img src="{{ $img }}" alt="" loading="lazy" class="absolute inset-0 h-full w-full object-cover">
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach
            <a href="{{ route('pages.show', 'custom-manufacturing') }}" @class(['nav-link hidden xl:flex', 'is-active' => request()->is('custom-manufacturing')])>Private label</a>
        </nav>

        <div class="flex items-center gap-1">
            <x-search-box id="header-search" class="hidden md:block" input-class="w-44 lg:w-40 xl:w-60" dropdown-class="right-0 w-[min(28rem,calc(100vw-2rem))]"/>
            @auth
                <div x-data="{ open: false }" class="relative" @click.outside="open = false" @keydown.escape="open = false">
                    <button type="button" class="btn btn-ghost btn-icon" @click="open = !open" :aria-expanded="open" aria-haspopup="true" aria-label="Account menu"><x-icon name="user"/></button>
                    <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="card absolute right-0 z-50 mt-2 w-60 p-1.5 text-sm shadow-lg">
                        <p class="truncate px-3 pb-2 pt-1.5 text-xs text-muted">{{ auth()->user()->email }}</p>
                        @if (auth()->user()->can('admin.access'))
                            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 hover:bg-elevated"><x-icon name="settings" class="h-4 w-4 text-muted"/>Admin panel</a>
                        @endif
                        <a href="{{ route('account.dashboard') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 hover:bg-elevated"><x-icon name="home" class="h-4 w-4 text-muted"/>My account</a>
                        <a href="{{ route('account.quotes.index') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 hover:bg-elevated"><x-icon name="clipboard" class="h-4 w-4 text-muted"/>My quotes</a>
                        <a href="{{ route('account.favorites') }}" class="flex items-center gap-2.5 rounded-md px-3 py-2 hover:bg-elevated"><x-icon name="heart" class="h-4 w-4 text-muted"/>Saved products</a>
                        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-border pt-1">@csrf<button class="flex w-full items-center gap-2.5 rounded-md px-3 py-2 text-left hover:bg-elevated"><x-icon name="logout" class="h-4 w-4 text-muted"/>Sign out</button></form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="btn btn-ghost btn-icon" aria-label="Sign in" title="Sign in" data-testid="sign-in"><x-icon name="user"/></a>
            @endauth
            <livewire:quote-basket-count/>
        </div>
    </div>

    {{-- Mobile menu: scrolls inside itself, so nothing below a sticky header is unreachable. --}}
    <nav id="mobile-nav" x-show="mobile" x-cloak x-transition.opacity.duration.200ms class="max-h-[calc(100dvh-4.5rem)] overflow-y-auto overscroll-contain border-t border-border bg-bg lg:hidden" aria-label="Mobile">
        <div class="container-page py-5">
            <x-search-box id="mobile-search" class="mb-4 md:hidden" dropdown-class="left-0 right-0"/>
            <ul class="divide-y divide-border border-y border-border">
                <li><a href="{{ route('catalog') }}" class="flex items-center justify-between py-3.5 font-medium">All products <x-icon name="arrow-right" class="h-4 w-4 text-muted"/></a></li>
                @foreach ($navCategories as $cat)
                    <li x-data="{ sub: false }">
                        <div class="flex items-center justify-between">
                            <a href="{{ route('catalog.category', $cat) }}" class="flex-1 py-3.5 font-serif text-xl">{{ $navLabel($cat) }}</a>
                            @if ($cat->children->isNotEmpty())
                                <button type="button" class="btn btn-ghost btn-icon text-muted" @click="sub = !sub" :aria-expanded="sub" aria-label="Show {{ $navLabel($cat) }} subcategories"><x-icon name="chevron-down" class="h-4 w-4 transition-transform" x-bind:class="sub && 'rotate-180'"/></button>
                            @endif
                        </div>
                        @if ($cat->children->isNotEmpty())
                            <ul x-show="sub" x-cloak x-transition class="mb-3 grid grid-cols-2 gap-1">
                                @foreach ($cat->children as $child)
                                    <li><a href="{{ route('catalog.category', $child) }}" class="block rounded-md bg-surface px-3 py-2 text-sm text-muted hover:text-text">{{ $child->name }}</a></li>
                                @endforeach
                            </ul>
                        @endif
                    </li>
                @endforeach
                <li><a href="{{ route('pages.show', 'custom-manufacturing') }}" class="block py-3.5">Private label &amp; manufacturing</a></li>
                <li><a href="{{ route('pages.show', 'about') }}" class="block py-3.5">About {{ $brand['name'] }}</a></li>
                <li><a href="{{ route('contact') }}" class="block py-3.5">Contact us</a></li>
            </ul>
            <div class="mt-5 grid gap-2 text-sm text-muted sm:hidden">
                <a href="tel:{{ $tel }}" class="inline-flex items-center gap-2"><x-icon name="phone" class="h-4 w-4 text-clay"/>{{ $brand['phone'] }}</a>
                <a href="mailto:{{ $brand['email'] }}" class="inline-flex items-center gap-2"><x-icon name="mail" class="h-4 w-4 text-clay"/>{{ $brand['email'] }}</a>
            </div>
            @guest
                <div class="mt-5 flex gap-2"><a href="{{ route('login') }}" class="btn btn-secondary flex-1">Sign in</a><a href="{{ route('register') }}" class="btn btn-dark flex-1">Open trade account</a></div>
            @endguest
        </div>
    </nav>
</header>
