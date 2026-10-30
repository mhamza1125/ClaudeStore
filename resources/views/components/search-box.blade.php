{{--
    Header product search with live suggestions (ARIA combobox). Typing shows matching
    products after a short debounce; Enter on a highlighted result opens it, otherwise
    the form submits normally to the full catalogue search (/products?q=…).
--}}
@props(['id', 'inputClass' => '', 'dropdownClass' => 'left-0 right-0'])
<form action="{{ route('catalog') }}" method="get" role="search" {{ $attributes->merge(['class' => 'relative']) }}
      x-data="searchSuggest(@js(route('search.suggest')), @js($id))"
      @click.outside="close()" @keydown.escape.prevent="close(); $refs.input.focus()" @submit="onSubmit($event)">
    <label for="{{ $id }}-input" class="sr-only">Search products by name or SKU</label>
    <input id="{{ $id }}-input" x-ref="input" name="q" value="{{ request('q') }}" placeholder="Search name or SKU" autocomplete="off" spellcheck="false"
           x-model="query" @input="onInput()" @focus="if (results.length || state === 'empty') open = true"
           @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.tab="close()"
           role="combobox" aria-autocomplete="list" :aria-expanded="open" aria-controls="{{ $id }}-listbox"
           :aria-activedescendant="active >= 0 ? '{{ $id }}-option-' + active : null"
           class="input pl-8 pr-8 {{ $inputClass }}">
    <x-icon name="search" class="pointer-events-none absolute left-2.5 top-2.5 h-4 w-4 text-muted"/>
    <span x-show="state === 'loading'" x-cloak class="absolute right-2.5 top-2.5 h-4 w-4 animate-spin rounded-full border-2 border-border-strong border-t-accent" aria-hidden="true"></span>
    <button type="submit" class="sr-only">Search</button>

    <div x-show="open" x-cloak x-transition.opacity.duration.100ms
         class="absolute top-full z-50 mt-2 overflow-hidden rounded-md border border-border bg-surface text-sm shadow-xl {{ $dropdownClass }}">
        <p class="sr-only" role="status" aria-live="polite" x-text="announcement"></p>
        <template x-if="state === 'loading' && !results.length">
            <p class="px-4 py-3 text-muted">Searching…</p>
        </template>
        <template x-if="state === 'error'">
            <p class="px-4 py-3 text-danger">Suggestions are unavailable right now. Press Enter to search all products.</p>
        </template>
        <template x-if="state === 'empty'">
            <p class="px-4 py-3 text-muted">No products match “<span x-text="lastQuery"></span>”. Try a SKU, or <a :href="allUrl" class="link">search the catalogue</a>.</p>
        </template>
        <ul id="{{ $id }}-listbox" role="listbox" aria-label="Product suggestions" x-show="results.length" class="max-h-[min(26rem,70vh)] overflow-y-auto py-1">
            <template x-for="(item, i) in results" :key="item.url">
                <li :id="'{{ $id }}-option-' + i" role="option" :aria-selected="active === i">
                    <a :href="item.url" @mouseenter="active = i" tabindex="-1"
                       class="flex items-center gap-3 px-3 py-2" :class="active === i ? 'bg-elevated' : ''">
                        <span class="relative h-12 w-10 shrink-0 overflow-hidden rounded-sm bg-elevated">
                            <img x-show="item.image" :src="item.image" alt="" class="protected-img h-full w-full object-cover" loading="lazy" draggable="false">
                            <template x-if="item.image"><x-image-watermark size="xs"/></template>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate font-medium" x-text="item.name"></span>
                            <span class="block truncate text-xs text-muted" x-text="[item.sku, item.category].filter(Boolean).join(' · ')"></span>
                        </span>
                        <span class="shrink-0 text-right text-xs font-medium" x-text="item.price"></span>
                    </a>
                </li>
            </template>
        </ul>
        <a x-show="results.length" :href="allUrl" class="block border-t border-border px-4 py-2.5 text-xs font-medium text-accent hover:bg-elevated">
            See all <span x-text="total"></span> results for “<span x-text="lastQuery"></span>”
        </a>
    </div>
</form>
