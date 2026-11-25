@props(['title' => null])
<!DOCTYPE html>
<html lang="en">
<head>
    @include('partials.head', ['title' => ($title ? $title.' · ' : '').'Admin', 'noindex' => true])
</head>
@php
    $u = auth()->user();
    // "New since you last looked" counts per navigation entry (App\Services\AdminActivity).
    $badges = app(\App\Services\AdminActivity::class)->counts($u);
    $badgeLabels = collect(\App\Services\AdminActivity::SECTIONS)->map(fn ($s) => $s[1])->all();
    // Grouped by job: sell, catalogue, customers, storefront, system.
    $groups = collect([
        'Sales' => [
            ['admin.dashboard', 'Dashboard', 'home', true],
            ['admin.quotes.index', 'Quotes', 'clipboard', true],
            ['admin.orders.index', 'Sales orders', 'box', $u->can('orders.manage')],
        ],
        'Catalogue' => [
            ['admin.products.index', 'Products', 'tag', $u->can('products.manage')],
            ['admin.categories', 'Categories', 'folder', $u->can('products.manage')],
            ['admin.csv.index', 'Import / export', 'upload', $u->can('products.manage')],
        ],
        'Customers' => [
            ['admin.companies.index', 'Customers', 'users', $u->can('customers.view')],
            ['admin.customer-accounts', 'Customer accounts', 'user', $u->can('customers.view')],
            ['admin.messages', 'Messages', 'mail', $u->can('customers.view')],
            ['admin.groups', 'Customer groups', 'layers', $u->can('customers.manage')],
        ],
        'Storefront' => [
            ['admin.hero-slides', 'Homepage slides', 'image', $u->can('settings.manage')],
            ['admin.b2b', 'B2B settings', 'shield', $u->can('settings.manage')],
            ['admin.settings', 'Settings', 'settings', $u->can('settings.manage')],
        ],
        'System' => [
            ['admin.staff', 'Staff & roles', 'badge-check', $u->can('staff.manage')],
            ['admin.audit', 'Audit log', 'list', $u->can('audit.view')],
        ],
    ])->map(fn ($items) => array_values(array_filter($items, fn ($i) => $i[3])))->filter();
    $initials = \Illuminate\Support\Str::upper(collect(explode(' ', $u->name))->filter()->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode(''));
@endphp
{{-- Badges refresh every minute (and when the tab regains focus) without reloading the page. --}}
<body class="min-h-screen bg-bg" x-data="{
        nav: false,
        counts: @js($badges),
        get total() { return Object.values(this.counts).reduce((a, b) => a + b, 0) },
        poll() {
            if (document.hidden) return;
            fetch(@js(route('admin.activity')), { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then(r => r.ok ? r.json() : null).then(d => { if (d) this.counts = d.counts }).catch(() => {});
        },
        init() { setInterval(() => this.poll(), 60000); document.addEventListener('visibilitychange', () => this.poll()); },
    }" @keydown.escape.window="nav = false">
<a href="#main" class="btn btn-primary sr-only focus:not-sr-only focus:fixed focus:left-4 focus:top-4 focus:z-50">Skip to content</a>
<div class="flex min-h-screen">
    <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-border bg-surface transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0" :class="nav && 'translate-x-0'" aria-label="Admin navigation">
        <div class="flex h-16 shrink-0 items-center justify-between border-b border-border px-5">
            <x-logo size="h-9"/>
            <span class="rounded-full bg-band px-2 py-0.5 text-[10px] font-semibold uppercase tracking-[0.14em] text-on-band">Admin</span>
        </div>
        <nav class="flex-1 overflow-y-auto px-3 pb-6">
            @foreach ($groups as $group => $items)
                <p class="admin-nav-group">{{ $group }}</p>
                <div class="space-y-0.5">
                    @foreach ($items as [$route, $label, $icon])
                        @php $on = request()->routeIs(str_replace('.index', '', $route).'*'); @endphp
                        <a href="{{ route($route) }}" @class(['admin-nav-link', 'is-active' => $on]) @if ($on) aria-current="page" @endif>
                            <x-icon :name="$icon" class="h-[17px] w-[17px] shrink-0"/> {{ $label }}
                            @if (array_key_exists($route, $badges))
                                @php $n = $badges[$route]; @endphp
                                <span class="ml-auto min-w-5 rounded-full bg-primary px-1.5 text-center text-[0.6875rem] font-bold leading-5 text-on-primary tabular-nums"
                                      data-badge="{{ $route }}" x-show="counts[@js($route)] > 0" x-text="counts[@js($route)] > 99 ? '99+' : counts[@js($route)]"
                                      :title="counts[@js($route)] + ' ' + @js($badgeLabels[$route])" title="{{ $n }} {{ $badgeLabels[$route] }}" @if (! $n) style="display: none" @endif>{{ $n > 99 ? '99+' : $n }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>
        <div class="shrink-0 border-t border-border p-3">
            <a href="{{ route('home') }}" class="admin-nav-link" target="_blank" rel="noopener"><x-icon name="external" class="h-[17px] w-[17px]"/> View storefront</a>
        </div>
    </aside>
    <div x-show="nav" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-overlay lg:hidden" @click="nav = false"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-border px-4 sm:px-6 lg:px-10" style="background: color-mix(in srgb, var(--color-bg) 90%, transparent); backdrop-filter: blur(12px)">
            <div class="flex min-w-0 items-center gap-3">
                <button type="button" class="btn btn-ghost btn-icon relative lg:hidden" @click="nav = true" aria-label="Open navigation">
                    <x-icon name="menu"/>
                    <span x-show="total > 0" x-cloak class="absolute right-1 top-1 h-2.5 w-2.5 rounded-full bg-primary ring-2 ring-bg" aria-hidden="true"></span>
                    <span class="sr-only" x-show="total > 0" x-text="'(' + total + ' new)'"></span>
                </button>
                <p class="truncate text-sm"><span class="text-muted">Admin</span>@if ($title)<span class="mx-2 text-border-strong">/</span><span class="font-medium">{{ $title }}</span>@endif</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="flex min-w-0 items-center gap-3" data-testid="admin-user">
                    <div class="hidden flex-col items-end gap-0.5 leading-tight sm:flex">
                        <span class="max-w-48 truncate text-sm font-medium">{{ $u->name }}</span>
                        <span class="flex flex-wrap justify-end gap-1">
                            @foreach ($u->getRoleNames() as $role)<x-badge tone="accent" class="!px-1.5 !py-0 text-[0.6875rem]">{{ \Illuminate\Support\Str::headline($role) }}</x-badge>@endforeach
                        </span>
                    </div>
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-band text-xs font-semibold text-on-band" aria-hidden="true">{{ $initials }}</span>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-icon" aria-label="Sign out" title="Sign out"><x-icon name="logout" class="h-4 w-4"/></button></form>
            </div>
        </header>
        <main class="flex-1 px-4 py-8 sm:px-6 lg:px-10" id="main">
            <x-flash/>
            @if (auth()->user()?->hasAnyRole(['admin', 'super-admin']))
                {{-- Background processing health (audit F-06); see docs/HOSTINGER.md. --}}
                @foreach (app(\App\Services\SystemHealth::class)->warnings() as $key => $message)
                    <div class="mb-4 alert badge-warning" role="alert" data-health-warning="{{ $key }}">{{ $message }}</div>
                @endforeach
            @endif
            {{ $slot }}
        </main>
    </div>
</div>
@livewireScriptConfig
</body>
</html>
