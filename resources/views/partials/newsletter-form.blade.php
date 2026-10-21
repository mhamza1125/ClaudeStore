<form method="POST" action="{{ route('newsletter.subscribe') }}" class="{{ ($compact ?? false) ? 'space-y-2' : 'flex flex-col gap-3 sm:flex-row' }}">
    @csrf
    <label for="nl-email-{{ ($compact ?? false) ? 'f' : 'h' }}" class="sr-only">Email address</label>
    <input id="nl-email-{{ ($compact ?? false) ? 'f' : 'h' }}" type="email" name="email" required placeholder="you@company.com" class="input">
    <div class="hidden" aria-hidden="true"><input type="text" name="website_url" tabindex="-1" autocomplete="off"></div>
    <button class="btn btn-primary {{ ($compact ?? false) ? 'btn-sm w-full' : '' }}">Subscribe</button>
    @error('email', 'newsletter')<p class="field-error">{{ $message }}</p>@enderror
</form>
