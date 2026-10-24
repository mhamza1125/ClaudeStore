<x-layouts.store :canonical="$canonical" :title="$title">
    <div class="container-page max-w-3xl py-16">
        <x-page-header :title="$title" eyebrow="Policies"/>
        @if (filled($content))
            <div class="prose-store whitespace-pre-line">{{ $content }}</div>
        @else
            <div class="panel">
                <p class="font-medium">This policy has not been published yet.</p>
                <p class="mt-2 text-sm text-muted">The business must supply this text (Admin → Settings → Policies). Until then, contact <a class="link" href="mailto:{{ $brand['email'] }}">{{ $brand['email'] }}</a> for {{ strtolower($title) }} information.</p>
            </div>
        @endif
    </div>
</x-layouts.store>
