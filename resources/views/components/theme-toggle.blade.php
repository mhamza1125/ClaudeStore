@props(['force' => false])
@if ($force || \App\Support\Theme::allowSwitch())
    <div x-data="themeToggle" class="relative" @keydown.escape="open = false" @click.outside="open = false">
        <button type="button" class="btn-ghost btn btn-sm !px-2" @click="open = !open" :aria-expanded="open" aria-haspopup="true" aria-label="Colour theme">
            @foreach (\App\Support\Theme::CHOICES as $value => [$label, $icon])
                <span x-show="choice === '{{ $value }}'" @if ($value !== 'mid') x-cloak @endif><x-icon :name="$icon" class="h-4 w-4"/></span>
            @endforeach
        </button>
        <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 z-50 mt-2 w-40 card py-1 shadow-lg" role="menu" aria-label="Colour theme">
            @foreach (\App\Support\Theme::CHOICES as $value => [$label, $icon])
                <button type="button" role="menuitemradio" :aria-checked="choice === '{{ $value }}'" @click="set('{{ $value }}')"
                    class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-elevated" :class="choice === '{{ $value }}' && 'text-accent font-medium'">
                    <x-icon :name="$icon" class="h-4 w-4"/> {{ $label }}
                    @if ($value === 'system')<span class="ml-auto text-xs text-muted">Auto</span>@endif
                </button>
            @endforeach
        </div>
    </div>
@endif
