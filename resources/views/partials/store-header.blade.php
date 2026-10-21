@php
    $navCategories = \Illuminate\Support\Facades\Cache::remember('nav.categories', 600, fn () =>
        \App\Models\Category::active()->roots()->orderBy('sort_order')->with(['children' => fn ($q) => $q->where('is_active', true)])->get());
@endphp
<header x-data="{ mobile: false }" class="sticky top-0 z-40 border-b border-border bg-bg/95 backdrop-blur">
    <div class="hidden border-b border-border bg-primary text-on-primary sm:block">
        <div class="container-page flex h-9 items-center justify-between text-xs tracking-wide">
            <p>Wholesale &amp; private-label leather goods · Trade pricing for approved accounts</p>
            <div class="flex items-center gap-5">
                <a href="mailto:{{ $brand['email'] }}" class="hover:underline">{{ $brand['email'] }}</a>
                <a href="{{ route('quote.basket') }}" class="hover:underline">Request a wholesale quote</a>
            </div>
        </div>
    </div>
    <div class="container-page flex h-18 items-center justify-between gap-4 py-3">
        <div class="flex items-center gap-3">
            <button type="button" class="btn btn-ghost btn-sm !px-2 lg:hidden" @click="mobile = !mobile" :aria-expanded="mobile" aria-controls="mobile-nav" aria-label="Menu">
                <x-icon name="menu"/>
            </button>
            <x-logo/>
        </div>

        <nav class="hidden items-center gap-7 text-sm lg:flex" aria-label="Main">
            @foreach ($navCategories as $cat)
                <div class="group relative">
                    <a href="{{ route('catalog.category', $cat) }}" class="flex items-center gap-1 py-2 hover:text-accent {{ request()->is('categories/'.$cat->slug) ? 'text-accent' : '' }}">
                        {{ $cat->name }}@if ($cat->children->isNotEmpty())<x-icon name="chevron-down" class="h-3.5 w-3.5"/>@endif
                    </a>
                    @if ($cat->children->isNotEmpty())
                        <div class="invisible absolute left-0 top-full z-50 w-56 card py-2 opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                            @foreach ($cat->children as $child)
                                <a href="{{ route('catalog.category', $child) }}" class="block px-4 py-2 hover:bg-elevated">{{ $child->name }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endforeach
            <a href="{{ route('pages.show', 'custom-manufacturing') }}" class="py-2 hover:text-accent">Private label</a>
        </nav>

        <div class="flex items-center gap-1">
            <form action="{{ route('catalog') }}" method="get" class="relative hidden md:block" role="search">
                <label for="header-search" class="sr-only">Search products, SKU or code</label>
                <input id="header-search" name="q" value="{{ request('q') }}" placeholder="Search name or SKU" class="input w-48 pl-8 xl:w-60">
                <x-icon name="search" class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-muted"/>
            </form>
            <x-theme-toggle/>
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
                <a href="{{ route('login') }}" class="btn btn-ghost btn-sm hidden sm:inline-flex">Sign in</a>
            @endauth
            <livewire:quote-basket-count/>
        </div>
    </div>

    <nav id="mobile-nav" x-show="mobile" x-cloak x-transition class="border-t border-border bg-surface lg:hidden" aria-label="Mobile">
        <div class="container-page space-y-1 py-4">
            <form action="{{ route('catalog') }}" method="get" class="mb-3" role="search">
                <label for="mobile-search" class="sr-only">Search</label>
                <input id="mobile-search" name="q" placeholder="Search name or SKU" class="input">
            </form>
            <a href="{{ route('catalog') }}" class="block py-2 font-medium">All products</a>
            @foreach ($navCategories as $cat)
                <a href="{{ route('catalog.category', $cat) }}" class="block py-2">{{ $cat->name }}</a>
                @foreach ($cat->children as $child)
                    <a href="{{ route('catalog.category', $child) }}" class="block py-1.5 pl-4 text-sm text-muted">{{ $child->name }}</a>
                @endforeach
            @endforeach
            <a href="{{ route('pages.show', 'custom-manufacturing') }}" class="block py-2">Private label &amp; custom</a>
            @guest
                <div class="flex gap-2 pt-3"><a href="{{ route('login') }}" class="btn btn-secondary flex-1">Sign in</a><a href="{{ route('register') }}" class="btn btn-primary flex-1">Open trade account</a></div>
            @endguest
        </div>
    </nav>
</header>
