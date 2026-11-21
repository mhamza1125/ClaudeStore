<x-layouts.store title="Contact Us" :canonical="route('contact')"
    description="Contact {{ $brand['name'] }} about leather products, bulk and wholesale orders, custom and private-label manufacturing, or support. Phone {{ $brand['phone'] }}, email {{ $brand['email'] }}.">
    @php
        $tel = preg_replace('/[^\d+]/', '', $brand['phone']);
        $whatsapp = \App\Support\WhatsApp::configuredLink();
    @endphp
    <section class="relative overflow-hidden border-b border-border">
        <div class="hero-backdrop !w-[34%] max-md:hidden" aria-hidden="true"></div>
        <div class="container-page relative grid gap-10 pb-12 pt-8 sm:pb-16 sm:pt-10 lg:grid-cols-12 lg:items-end">
            <div class="lg:col-span-7">
                <x-breadcrumbs :items="[['Home', route('home')], ['Contact us', route('contact')]]" class="mb-10"/>
                <p class="eyebrow eyebrow-stitch mb-5">Contact us</p>
                <h1 class="h-section max-w-3xl">Contact us for any questions</h1>
                <p class="lead mt-5 max-w-2xl !text-lg">Product details, bulk and wholesale orders, customisation or private label: send us a message and our team will get back to you quickly. For an instant answer, call or message us on WhatsApp.</p>
            </div>
            {{-- Quick contact: Sialkot local time (live), reply time and one-tap actions. No opening
                 hours are shown because none are configured. --}}
            <aside class="lg:col-span-4 lg:col-start-9" aria-label="Quick contact">
                <div class="card overflow-hidden shadow-md"
                     x-data="{ time: @js(now('Asia/Karachi')->format('g:i a')), tick() { this.time = new Intl.DateTimeFormat('en-US', { timeZone: 'Asia/Karachi', hour: 'numeric', minute: '2-digit' }).format(new Date()).toLowerCase() } }"
                     x-init="tick(); setInterval(() => tick(), 30000)">
                    <div class="flex items-center gap-4 bg-band px-6 py-5 text-on-band">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-band-2 text-thread"><x-icon name="clock" class="h-5 w-5"/></span>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-hide">Local time in Sialkot</p>
                            <p class="mt-0.5 font-serif text-2xl leading-tight tabular-nums"><span x-text="time">{{ now('Asia/Karachi')->format('g:i a') }}</span> <span class="font-sans text-sm text-on-band/60">PKT · UTC+5</span></p>
                        </div>
                    </div>
                    <div class="px-6 py-5">
                        <p class="text-sm text-muted">Reach us directly</p>
                        <div class="mt-3 grid gap-2 {{ $whatsapp ? 'grid-cols-3' : 'grid-cols-2' }} [&_.btn]:gap-1.5">
                            <a href="tel:{{ $tel }}" class="btn btn-secondary btn-sm !px-2"><x-icon name="phone" class="h-4 w-4"/> Call</a>
                            @if ($whatsapp)
                                <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary btn-sm !px-2" aria-label="WhatsApp (opens in a new tab)">
                                    <svg viewBox="0 0 24 24" class="h-4 w-4 shrink-0" fill="currentColor" aria-hidden="true"><path d="M12.04 2a9.9 9.9 0 0 0-8.5 15l-1.4 5 5.1-1.3A9.9 9.9 0 1 0 12.04 2Zm0 18.1a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3a8.2 8.2 0 1 1 6.9 3.8Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.8 11.9 11.9 0 0 0 4.6 4c1.7.7 2.4.8 3.2.7.5-.1 1.5-.6 1.8-1.2.2-.6.2-1.1.1-1.2l-.5-.2Z"/></svg> WhatsApp
                                </a>
                            @endif
                            <a href="mailto:{{ $brand['email'] }}" class="btn btn-primary btn-sm !px-2"><x-icon name="mail" class="h-4 w-4"/> Email</a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </section>

    <div class="container-page grid gap-10 py-12 lg:grid-cols-12 lg:gap-14 lg:py-16">
        {{-- Official contact details --}}
        <aside class="space-y-4 lg:col-span-5" aria-labelledby="contact-details">
            <h2 id="contact-details" class="h-product">Get in touch</h2>
            <ul class="card divide-y divide-border" data-testid="contact-details">
                <li class="flex items-start gap-4 p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-clay-soft text-clay"><x-icon name="phone" class="h-5 w-5"/></span>
                    <span><span class="block text-xs font-semibold uppercase tracking-[0.12em] text-muted">Phone</span><a href="tel:{{ $tel }}" class="text-lg font-medium hover:text-accent">{{ $brand['phone'] }}</a></span>
                </li>
                <li class="flex items-start gap-4 p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-clay-soft text-clay"><x-icon name="mail" class="h-5 w-5"/></span>
                    <span class="min-w-0"><span class="block text-xs font-semibold uppercase tracking-[0.12em] text-muted">Email</span><a href="mailto:{{ $brand['email'] }}" class="break-all text-lg font-medium hover:text-accent">{{ $brand['email'] }}</a></span>
                </li>
                <li class="flex items-start gap-4 p-5">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-clay-soft text-clay"><x-icon name="pin" class="h-5 w-5"/></span>
                    <span><span class="block text-xs font-semibold uppercase tracking-[0.12em] text-muted">Address</span><span class="text-lg font-medium">{{ $brand['address'] }}</span></span>
                </li>
            </ul>
            @if ($whatsapp)
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary w-full">Chat with us on WhatsApp</a>
            @endif
            @if (\App\Support\Social::visible())
                <div class="panel flex flex-wrap items-center justify-between gap-4 !py-4">
                    <p class="text-sm font-medium">Follow {{ $brand['name'] }}</p>
                    <x-social-links/>
                </div>
            @endif
            <div class="band grain stitch-frame rounded-lg p-6 text-sm" style="--stitch-color: rgb(217 154 99 / .4); --stitch-inset: 8px">
                <p class="font-serif text-xl text-on-band">Planning an order?</p>
                <p class="mt-2 text-on-band/75">For pricing on specific products and quantities, add them to your <a href="{{ route('quote.basket') }}" class="link">quote list</a> and send a quote request. For your own designs or branding, see <a href="{{ route('pages.show', 'custom-manufacturing') }}" class="link">custom &amp; private label</a>.</p>
            </div>
        </aside>

        {{-- Form --}}
        <section class="lg:col-span-7" aria-labelledby="contact-form-heading">
            <div class="panel shadow-sm sm:p-9">
                @if (session('contact_sent'))
                    <div class="py-6 text-center" role="status" data-testid="contact-success">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full badge-success"><x-icon name="check" class="h-7 w-7"/></span>
                        <h2 id="contact-form-heading" class="mt-5 text-3xl">Thank you, your message has been sent</h2>
                        <p class="mx-auto mt-2 max-w-md text-muted">We have received it and will reply to your email as soon as possible.</p>
                        <a href="{{ route('contact') }}" class="btn btn-secondary mt-6">Send another message</a>
                    </div>
                @else
                    <h2 id="contact-form-heading" class="h-product">Send us a message</h2>
                    <p class="mt-1 text-sm text-muted">Fields marked <span class="text-danger">*</span> are required.</p>

                    @if (session('warning'))
                        <div class="alert badge-warning mt-5" role="alert">{{ session('warning') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="alert badge-danger mt-5" role="alert" data-testid="contact-errors">
                            @error('form'){{ $message }}@else Your message was not sent. Please correct the highlighted fields and try again.@enderror
                        </div>
                    @endif

                    <form method="POST" action="{{ route('contact.store') }}" class="mt-6 grid gap-5 sm:grid-cols-2" x-data="{ sending: false }" @submit="sending = true">
                        @csrf
                        <input type="hidden" name="form_started" value="{{ encrypt(now()->timestamp) }}">
                        <div class="hidden" aria-hidden="true"><label>Fax number<input type="text" name="fax_number" tabindex="-1" autocomplete="off"></label></div>

                        <x-field name="name" label="Your name" required maxlength="120" autocomplete="name" :value="auth()->user()?->name"/>
                        <x-field name="email" type="email" label="Your email" required maxlength="255" autocomplete="email" :value="auth()->user()?->email"/>
                        <x-field name="phone" type="tel" label="Phone number" maxlength="40" pattern="^\+?[0-9 ()\-.]{6,40}$" title="Digits, spaces and + ( ) - . only" placeholder="+92 300 0000000" autocomplete="tel" :value="auth()->user()?->phone"/>
                        <x-field name="company" label="Company" maxlength="160" autocomplete="organization" :value="auth()->user()?->company?->name"/>
                        <x-select-field name="subject" label="Subject" placeholder="Choose a topic…" :options="array_combine($subjects, $subjects)" class="sm:col-span-2"/>
                        <x-textarea-field name="message" label="Your message" required rows="6" minlength="10" maxlength="5000" placeholder="Tell us about the products, quantities or support you need." class="sm:col-span-2"/>

                        <div class="flex flex-wrap items-center gap-4 sm:col-span-2">
                            <button type="submit" class="btn btn-primary btn-lg" :disabled="sending">
                                <span x-show="!sending">Send message</span>
                                <span x-show="sending" x-cloak>Sending…</span>
                            </button>
                            <p class="text-xs text-muted">We use your details only to answer this message. See our <a href="{{ route('pages.show', 'privacy') }}" class="link">privacy policy</a>.</p>
                        </div>
                    </form>
                @endif
            </div>
        </section>
    </div>
</x-layouts.store>
