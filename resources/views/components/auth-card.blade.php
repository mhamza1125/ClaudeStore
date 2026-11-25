@props(['title', 'eyebrow' => 'Trade accounts'])
{{-- Sign in / register / password screens: brand panel + form, one layout for all auth pages. --}}
<x-layouts.store :title="$title" :noindex="true">
    <div class="container-page py-10 sm:py-16">
        <div class="mx-auto grid max-w-5xl overflow-hidden rounded-xl border border-border bg-surface shadow-md lg:grid-cols-[1fr_1.1fr]">
            <aside class="band grain relative hidden flex-col justify-between p-10 lg:flex" aria-label="Why open a trade account">
                <div class="stitch-frame absolute inset-0" style="--stitch-color: rgb(217 154 99 / .35); --stitch-radius: 24px; --stitch-inset: 14px" aria-hidden="true"></div>
                <div>
                    <p class="eyebrow eyebrow-stitch mb-5">{{ $eyebrow }}</p>
                    <p class="font-serif text-[2.25rem] leading-[1.08]">Wholesale pricing, <span class="italic text-hide">in one place.</span></p>
                </div>
                <ul class="mt-10 space-y-4 text-sm text-on-band/80">
                    @foreach (['See your group and negotiated prices on every product', 'Track quotations and accept them online', 'Reorder from past quotes and sales orders', 'Save products and share access with your team'] as $point)
                        <li class="flex gap-3"><span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-thread/20 text-thread"><x-icon name="check" class="h-3 w-3"/></span>{{ $point }}</li>
                    @endforeach
                </ul>
            </aside>
            <div class="p-6 sm:p-10">
                <p class="eyebrow mb-3 lg:hidden">{{ $eyebrow }}</p>
                <h1 class="mb-8 text-[2rem] leading-tight sm:text-[2.5rem]" style="letter-spacing:-0.025em">{{ $title }}</h1>
                <x-flash/>
                {{ $slot }}
                @isset($below)<div class="mt-8 border-t border-border pt-6 text-sm text-muted">{{ $below }}</div>@endisset
            </div>
        </div>
    </div>
</x-layouts.store>
