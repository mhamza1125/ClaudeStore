@props(['title', 'eyebrow' => 'Trade accounts'])
<x-layouts.store :title="$title">
    <div class="container-page grid min-h-[70vh] place-items-center py-16">
        <div class="w-full max-w-md">
            <p class="eyebrow mb-2 text-center">{{ $eyebrow }}</p>
            <h1 class="mb-8 text-center text-4xl">{{ $title }}</h1>
            <x-flash/>
            <div class="panel">{{ $slot }}</div>
            @isset($below)<div class="mt-6 text-center text-sm text-muted">{{ $below }}</div>@endisset
        </div>
    </div>
</x-layouts.store>
