@props(['title' => null])
<!DOCTYPE html>
<html lang="en" data-user-theme="{{ auth()->user()->theme }}" data-theme-url="{{ route('preferences.theme') }}">
<head>
    @include('partials.head', ['title' => ($title ? $title.' · ' : '').'Admin'])
    <meta name="robots" content="noindex">
</head>
<body class="min-h-screen bg-bg" x-data="{ nav: false }">
@php
    $u = auth()->user();
    $items = array_filter([
        ['admin.dashboard', 'Dashboard', 'home', true],
        ['admin.quotes.index', 'Quotes', 'clipboard', true],
        ['admin.orders.index', 'Sales orders', 'box', $u->can('orders.manage')],
        ['admin.products.index', 'Products', 'tag', $u->can('products.manage')],
        ['admin.categories', 'Categories', 'folder', $u->can('products.manage')],
        ['admin.csv.index', 'Import / export', 'upload', $u->can('products.manage')],
        ['admin.companies.index', 'Customers', 'users', $u->can('customers.view')],
        ['admin.groups', 'Customer groups', 'users', $u->can('customers.manage')],
        ['admin.settings', 'Settings', 'settings', $u->can('settings.manage')],
        ['admin.staff', 'Staff & roles', 'shield', $u->can('staff.manage')],
        ['admin.audit', 'Audit log', 'list', $u->can('audit.view')],
    ], fn ($i) => $i[3]);
@endphp
<div class="flex min-h-screen">
    <aside class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full border-r border-border bg-surface transition-transform lg:static lg:translate-x-0" :class="nav && 'translate-x-0'" aria-label="Admin navigation">
        <div class="flex h-16 items-center border-b border-border px-5"><x-logo/></div>
        <nav class="space-y-0.5 p-3 text-sm">
            @foreach ($items as [$route, $label, $icon])
                <a href="{{ route($route) }}" class="flex items-center gap-3 rounded-sm px-3 py-2 {{ request()->routeIs(str_replace('.index', '', $route).'*') ? 'bg-elevated font-medium text-accent' : 'text-muted hover:bg-elevated hover:text-text' }}">
                    <x-icon :name="$icon" class="h-4 w-4"/> {{ $label }}
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
                <button type="button" class="btn btn-ghost btn-sm !px-2 lg:hidden" @click="nav = true" aria-label="Open navigation"><x-icon name="menu"/></button>
                <span class="text-sm text-muted">Admin</span>
            </div>
            <div class="flex items-center gap-2">
                <x-theme-toggle/>
                <span class="hidden text-sm sm:inline">{{ $u->name }} <span class="text-muted">· {{ $u->getRoleNames()->implode(', ') }}</span></span>
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
