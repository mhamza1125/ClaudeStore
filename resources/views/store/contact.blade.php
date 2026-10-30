<x-layouts.store title="Contact Us" :canonical="route('contact')"
    description="Contact {{ $brand['name'] }} about leather products, bulk and wholesale orders, custom and private-label manufacturing, or support. Phone {{ $brand['phone'] }}, email {{ $brand['email'] }}.">
    @php
        $tel = preg_replace('/[^\d+]/', '', $brand['phone']);
        $whatsapp = \App\Support\WhatsApp::configuredLink();
    @endphp
    <section class="border-b border-border bg-elevated/50">
        <div class="container-page py-12 sm:py-16">
            <p class="eyebrow mb-3">Contact us</p>
            <h1 class="max-w-3xl text-4xl uppercase leading-tight tracking-wide sm:text-5xl">Contact us for any questions</h1>
            <p class="mt-4 max-w-2xl text-muted">Product details, bulk and wholesale orders, customisation or private label: send us a message and our team will get back to you, usually within one business day.</p>
        </div>
    </section>

    <div class="container-page grid gap-10 py-12 lg:grid-cols-12 lg:py-16">
        {{-- Official contact details --}}
        <aside class="space-y-4 lg:col-span-5" aria-labelledby="contact-details">
            <h2 id="contact-details" class="text-3xl">Get in touch</h2>
            <ul class="space-y-3" data-testid="contact-details">
                <li class="panel flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-accent-soft text-accent"><x-icon name="phone" class="h-5 w-5"/></span>
                    <span><span class="block text-xs uppercase tracking-wider text-muted">Phone</span><a href="tel:{{ $tel }}" class="text-lg font-medium hover:text-accent">{{ $brand['phone'] }}</a></span>
                </li>
                <li class="panel flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-accent-soft text-accent"><x-icon name="mail" class="h-5 w-5"/></span>
                    <span><span class="block text-xs uppercase tracking-wider text-muted">Email</span><a href="mailto:{{ $brand['email'] }}" class="text-lg font-medium break-all hover:text-accent">{{ $brand['email'] }}</a></span>
                </li>
                <li class="panel flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-accent-soft text-accent"><x-icon name="pin" class="h-5 w-5"/></span>
                    <span><span class="block text-xs uppercase tracking-wider text-muted">Address</span><span class="text-lg font-medium">{{ $brand['address'] }}</span></span>
                </li>
            </ul>
            @if ($whatsapp)
                <a href="{{ $whatsapp }}" target="_blank" rel="noopener noreferrer" class="btn btn-secondary w-full">Chat with us on WhatsApp</a>
            @endif
            <div class="panel text-sm text-muted">
                <p class="font-medium text-text">Planning an order?</p>
                <p class="mt-1">For pricing on specific products and quantities, add them to your <a href="{{ route('quote.basket') }}" class="link">quote list</a> and send a quote request. For your own designs or branding, see <a href="{{ route('pages.show', 'custom-manufacturing') }}" class="link">custom &amp; private label</a>.</p>
            </div>
        </aside>

        {{-- Form --}}
        <section class="lg:col-span-7" aria-labelledby="contact-form-heading">
            <div class="panel sm:p-8">
                @if (session('contact_sent'))
                    <div class="py-6 text-center" role="status" data-testid="contact-success">
                        <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-full badge-success"><x-icon name="check" class="h-7 w-7"/></span>
                        <h2 id="contact-form-heading" class="mt-4 text-3xl">Thank you, your message has been sent</h2>
                        <p class="mx-auto mt-2 max-w-md text-muted">We have received it and will reply to your email as soon as possible, usually within one business day.</p>
                        <a href="{{ route('contact') }}" class="btn btn-secondary mt-6">Send another message</a>
                    </div>
                @else
                    <h2 id="contact-form-heading" class="text-3xl">Send us a message</h2>
                    <p class="mt-1 text-sm text-muted">Fields marked <span class="text-danger">*</span> are required.</p>

                    @if (session('warning'))
                        <div class="mt-5 rounded-sm border border-current/20 px-4 py-3 text-sm badge-warning" role="alert">{{ session('warning') }}</div>
                    @endif
                    @if ($errors->any())
                        <div class="mt-5 rounded-sm border border-current/20 px-4 py-3 text-sm badge-danger" role="alert" data-testid="contact-errors">
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
