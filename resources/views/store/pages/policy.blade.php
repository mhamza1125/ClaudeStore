<x-layouts.store :canonical="$canonical" :title="$title" :noindex="$noindex ?? false" :description="$title.' for trade customers of '.$brand['name'].': quotations, orders, delivery and claims.'">
    <section class="border-b border-border bg-elevated">
        <div class="container-page pb-10 pt-8 sm:pb-14 sm:pt-10">
            <x-breadcrumbs :items="[['Home', route('home')], [$title, $canonical]]" class="mb-8"/>
            <p class="eyebrow eyebrow-stitch mb-4">Policies</p>
            <h1 class="h-section">{{ $title }}</h1>
        </div>
    </section>
    <div class="container-page grid gap-12 py-12 lg:grid-cols-12 lg:gap-16 lg:py-16">
        <article class="min-w-0 lg:col-span-8">
            @if (filled($content))
                <div class="prose-store policy-text max-w-3xl">{{ $content }}</div>{{-- HtmlString from App\Support\Policies (raw HTML stripped) --}}
            @else
                <div class="panel max-w-3xl">
                    <p class="font-medium">This policy has not been published yet.</p>
                    <p class="mt-2 text-sm text-muted">The business must supply this text (Admin → Settings → Policies). Until then, contact <a class="link" href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a> for {{ strtolower($title) }} information.</p>
                </div>
            @endif
        </article>
        <aside class="lg:col-span-4" aria-label="More information">
            <div class="space-y-4 lg:sticky lg:top-32">
                <nav class="card p-2" aria-label="Policies">
                    @foreach (\App\Http\Controllers\PageController::POLICIES as $slug => $label)
                        @php $current = route('pages.show', $slug) === $canonical; @endphp
                        <a href="{{ route('pages.show', $slug) }}" @class(['flex items-center justify-between rounded-md px-4 py-3 text-sm transition-colors', 'bg-accent-soft font-semibold text-accent' => $current, 'hover:bg-elevated' => ! $current]) @if ($current) aria-current="page" @endif>
                            {{ $label }} <x-icon name="chevron-right" class="h-4 w-4 opacity-50"/>
                        </a>
                    @endforeach
                </nav>
                <div class="panel-cream flex flex-wrap items-center justify-between gap-4">
                    <p class="text-sm"><span class="block font-semibold">Questions about this policy?</span> <span class="text-muted">Our team is happy to help.</span></p>
                    <a href="{{ route('contact') }}" class="btn btn-secondary btn-sm">Contact us</a>
                </div>
            </div>
        </aside>
    </div>
</x-layouts.store>
