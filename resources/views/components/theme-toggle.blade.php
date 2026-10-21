<div x-data="themeToggle" class="relative" @keydown.escape="open = false" @click.outside="open = false">
    <button type="button" class="btn-ghost btn btn-sm !px-2" @click="open = !open" :aria-expanded="open" aria-haspopup="true" aria-label="Colour theme">
        <span x-show="choice === 'light'"><x-icon name="sun" class="h-4 w-4"/></span>
        <span x-show="choice === 'dark'" x-cloak><x-icon name="moon" class="h-4 w-4"/></span>
        <span x-show="choice === 'system'" x-cloak><x-icon name="monitor" class="h-4 w-4"/></span>
    </button>
    <div x-show="open" x-cloak x-transition.opacity class="absolute right-0 z-50 mt-2 w-36 card py-1 shadow-lg" role="menu">
        @foreach (['light' => ['Light', 'sun'], 'dark' => ['Dark', 'moon'], 'system' => ['System', 'monitor']] as $value => [$label, $icon])
            <button type="button" role="menuitemradio" :aria-checked="choice === '{{ $value }}'" @click="set('{{ $value }}')"
                class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm hover:bg-elevated" :class="choice === '{{ $value }}' && 'text-accent font-medium'">
                <x-icon :name="$icon" class="h-4 w-4"/> {{ $label }}
            </button>
        @endforeach
    </div>
</div>
