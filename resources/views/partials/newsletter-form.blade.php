<form method="POST" action="{{ route('newsletter.subscribe') }}" class="{{ ($compact ?? false) ? 'flex flex-wrap gap-2 [&>input]:flex-1' :'flex flex-col gap-3 sm:flex-row' }}">
    @csrf
    <label for="nl-email-{{ ($compact ?? false) ? 'f' : 'h' }}" class="sr-only">Email address</label>
    <input id="nl-email-{{ ($compact ?? false) ? 'f' : 'h' }}" type="email" name="email" required placeholder="you@company.com" class="input min-w-0 {{ ($compact ?? false) ? 'border-on-band/20 bg-band-2 text-on-band placeholder:text-on-band/45' : '' }}">
    <div class="hidden" aria-hidden="true"><input type="text" name="website_url" tabindex="-1" autocomplete="off"></div>
    {{-- Compact = footer, on the dark band. --}}
    <button class="btn {{ ($compact ?? false) ? 'btn-light shrink-0' : 'btn-primary' }}">Subscribe</button>
    @error('email', 'newsletter')<p class="field-error {{ ($compact ?? false) ? '!text-on-band' : '' }}">{{ $message }}</p>@enderror
</form>
