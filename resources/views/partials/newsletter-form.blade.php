<form method="POST" action="{{ route('newsletter.subscribe') }}" class="{{ ($compact ?? false) ? 'space-y-2' : 'flex flex-col gap-3 sm:flex-row' }}">
    @csrf
    <label for="nl-email-{{ ($compact ?? false) ? 'f' : 'h' }}" class="sr-only">Email address</label>
    <input id="nl-email-{{ ($compact ?? false) ? 'f' : 'h' }}" type="email" name="email" required placeholder="you@company.com" class="input">
    <div class="hidden" aria-hidden="true"><input type="text" name="website_url" tabindex="-1" autocomplete="off"></div>
    {{-- Compact = footer, which sits on the dark band: an outlined button reads on every palette. --}}
    <button class="btn {{ ($compact ?? false) ? 'btn-sm w-full border-on-band text-on-band hover:bg-on-band hover:text-band' : 'btn-primary' }}">Subscribe</button>
    @error('email', 'newsletter')<p class="field-error {{ ($compact ?? false) ? '!text-on-band' : '' }}">{{ $message }}</p>@enderror
</form>
