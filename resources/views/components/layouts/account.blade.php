@props(['title' => null])
<x-layouts.store :title="$title" :noindex="true">
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
        $initials = \Illuminate\Support\Str::upper(collect(explode(' ', $u->company?->name ?? $u->name))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''));
    @endphp
    <div class="container-page py-8 lg:py-12">
        <div class="grid gap-8 lg:grid-cols-[250px_1fr] lg:gap-12">
            <aside aria-label="Account navigation" class="lg:sticky lg:top-28 lg:self-start">
                <div class="mb-4 flex items-center gap-3 lg:mb-6">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-band font-serif text-on-band" aria-hidden="true">{{ $initials ?: 'LM' }}</span>
                    <div class="min-w-0"><p class="eyebrow">My account</p><p class="truncate font-serif text-lg leading-tight">{{ $u->company?->name ?? $u->name }}</p></div>
                </div>
                <nav class="no-scrollbar -mx-4 flex gap-1 overflow-x-auto px-4 text-sm lg:mx-0 lg:flex-col lg:rounded-lg lg:border lg:border-border lg:bg-surface lg:p-2 lg:px-2">
                    @foreach ($links as [$route, $label, $icon])
                        @php $on = request()->routeIs(str_replace('.index', '', $route).'*'); @endphp
                        <a href="{{ route($route) }}" @class(['flex shrink-0 items-center gap-2.5 rounded-full px-3.5 py-2 transition-colors lg:rounded-md', 'bg-accent-soft font-semibold text-accent' => $on, 'text-muted hover:bg-elevated hover:text-text' => ! $on]) @if ($on) aria-current="page" @endif><x-icon :name="$icon" class="h-4 w-4"/>{{ $label }}</a>
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
