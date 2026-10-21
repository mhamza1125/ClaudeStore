@props(['title' => null])
<x-layouts.store :title="$title">
    @php
        $u = auth()->user();
        $links = [
            ['account.dashboard', 'Overview', 'home'],
            ['account.quotes.index', 'Quotes', 'clipboard'],
            ['account.orders.index', 'Orders', 'box'],
            ['account.pricing', 'My pricing', 'tag'],
            ['account.favorites', 'Saved products', 'heart'],
            ['account.company', 'Company & addresses', 'folder'],
            ['account.team', 'Team', 'users'],
            ['account.profile', 'Profile & security', 'user'],
        ];
    @endphp
    <div class="container-page py-10">
        <div class="grid gap-10 lg:grid-cols-[220px_1fr]">
            <aside aria-label="Account navigation">
                <p class="eyebrow mb-1">My account</p>
                <p class="mb-5 font-serif text-xl">{{ $u->company?->name ?? $u->name }}</p>
                <nav class="flex gap-1 overflow-x-auto text-sm lg:flex-col">
                    @foreach ($links as [$route, $label, $icon])
                        <a href="{{ route($route) }}" class="flex shrink-0 items-center gap-2 rounded-sm px-3 py-2 {{ request()->routeIs(str_replace('.index', '', $route).'*') ? 'bg-elevated font-medium text-accent' : 'text-muted hover:bg-elevated hover:text-text' }}"><x-icon :name="$icon" class="h-4 w-4"/>{{ $label }}</a>
                    @endforeach
                </nav>
            </aside>
            <div class="min-w-0">
                <x-flash/>
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layouts.store>
