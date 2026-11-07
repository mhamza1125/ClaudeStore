@props(['title' => null])
<!DOCTYPE html>
<html lang="en" {!! \App\Support\Theme::htmlAttributes(forceSwitch: true) !!}>
<head>
    @include('partials.head', ['title' => ($title ? $title.' · ' : '').'Admin'])
    <meta name="robots" content="noindex">
</head>
@php
    $u = auth()->user();
    // "New since you last looked" counts per navigation entry (App\Services\AdminActivity).
    $badges = app(\App\Services\AdminActivity::class)->counts($u);
    $badgeLabels = collect(\App\Services\AdminActivity::SECTIONS)->map(fn ($s) => $s[1])->all();
    $items = array_filter([
        ['admin.dashboard', 'Dashboard', 'home', true],
        ['admin.quotes.index', 'Quotes', 'clipboard', true],
        ['admin.orders.index', 'Sales orders', 'box', $u->can('orders.manage')],
        ['admin.products.index', 'Products', 'tag', $u->can('products.manage')],
        ['admin.categories', 'Categories', 'folder', $u->can('products.manage')],
        ['admin.csv.index', 'Import / export', 'upload', $u->can('products.manage')],
        ['admin.companies.index', 'Customers', 'users', $u->can('customers.view')],
        ['admin.customer-accounts', 'Customer accounts', 'user', $u->can('customers.view')],
        ['admin.messages', 'Messages', 'mail', $u->can('customers.view')],
        ['admin.groups', 'Customer groups', 'users', $u->can('customers.manage')],
        ['admin.b2b', 'B2B settings', 'shield', $u->can('settings.manage')],
        ['admin.hero-slides', 'Homepage slides', 'image', $u->can('settings.manage')],
        ['admin.settings', 'Settings', 'settings', $u->can('settings.manage')],
        ['admin.staff', 'Staff & roles', 'shield', $u->can('staff.manage')],
        ['admin.audit', 'Audit log', 'list', $u->can('audit.view')],
    ], fn ($i) => $i[3]);
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
    }">
<div class="flex min-h-screen">
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full border-r border-border bg-surface transition-transform lg:static lg:translate-x-0" :class="nav && 'translate-x-0'" aria-label="Admin navigation">
        <div class="flex h-16 items-center border-b border-border px-5"><x-logo size="h-10"/></div>
        <nav class="space-y-0.5 p-3 text-sm">
            @foreach ($items as [$route, $label, $icon])
                <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-sm px-3 py-2 {{ request()->routeIs(str_replace('.index', '', $route).'*') ? 'bg-elevated font-medium text-accent' : 'text-muted hover:bg-elevated hover:text-text' }}">
                    <x-icon :name="$icon" class="h-4 w-4"/> {{ $label }}
                    @if (array_key_exists($route, $badges))
                        @php $n = $badges[$route]; @endphp
                        <span class="ml-auto min-w-5 rounded-full bg-accent px-1.5 text-center text-[0.6875rem] font-semibold leading-5 text-on-accent"
                              data-badge="{{ $route }}" x-show="counts[@js($route)] > 0" x-text="counts[@js($route)] > 99 ? '99+' : counts[@js($route)]"
                              :title="counts[@js($route)] + ' ' + @js($badgeLabels[$route])" title="{{ $n }} {{ $badgeLabels[$route] }}" @if (! $n) style="display: none" @endif>{{ $n > 99 ? '99+' : $n }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
        <div class="absolute bottom-0 w-full border-t border-border p-4 text-xs text-muted">
            <a href="{{ route('home') }}" class="flex items-center gap-2 hover:text-text"><x-icon name="external" class="h-4 w-4"/> View storefront</a>
        </div>
    </aside>
    <div x-show="nav" x-cloak class="fixed inset-0 z-30 bg-overlay lg:hidden" @click="nav = false"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-border bg-bg/95 px-4 backdrop-blur sm:px-6">
            <div class="flex items-center gap-3">
                <button type="button" class="btn btn-ghost btn-sm relative !px-2 lg:hidden" @click="nav = true" aria-label="Open navigation">
                    <x-icon name="menu"/>
                    <span x-show="total > 0" x-cloak class="absolute right-0.5 top-0.5 h-2.5 w-2.5 rounded-full bg-accent ring-2 ring-bg" aria-hidden="true"></span>
                    <span class="sr-only" x-show="total > 0" x-text="'(' + total + ' new)'"></span>
                </button>
                <span class="text-sm text-muted">Admin</span>
            </div>
            <div class="flex items-center gap-2">
                <x-theme-toggle :force="true"/>
                <div class="flex min-w-0 flex-col items-end gap-0.5 leading-tight" data-testid="admin-user">
                    <span class="hidden max-w-48 truncate text-sm font-medium sm:block">{{ $u->name }}</span>
                    <span class="flex flex-wrap justify-end gap-1">
                        @foreach ($u->getRoleNames() as $role)<x-badge tone="accent" class="!px-1.5 !py-0 text-[0.6875rem]">{{ \Illuminate\Support\Str::headline($role) }}</x-badge>@endforeach
                    </span>
                </div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm" aria-label="Sign out"><x-icon name="logout" class="h-4 w-4"/></button></form>
            </div>
        </header>
        <main class="flex-1 px-4 py-8 sm:px-6 lg:px-10" id="main">
            <x-flash/>
            {{ $slot }}
        </main>
    </div>
</div>
@livewireScriptConfig
</body>
</html>
