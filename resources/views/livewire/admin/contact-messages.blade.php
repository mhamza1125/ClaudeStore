<div>
    <x-page-header title="Messages" eyebrow="Contact Us form"/>
    <div class="mb-6 flex flex-wrap items-center gap-4">
        <div class="w-full sm:w-72"><label for="msg-search" class="sr-only">Search</label><input id="msg-search" type="search" wire:model.live.debounce.400ms="search" class="input" placeholder="Name, email, company or subject"></div>
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" class="checkbox" wire:model.live="unread"> Unread only</label>
    </div>

    <div class="card divide-y divide-border">
        @forelse ($messages as $m)
            <article wire:key="msg-{{ $m->id }}" class="px-5 py-4">
                <button type="button" wire:click="open({{ $m->id }})" class="flex w-full flex-wrap items-start justify-between gap-2 text-left" aria-expanded="{{ $openId === $m->id ? 'true' : 'false' }}">
                    <span class="min-w-0">
                        <span class="flex items-center gap-2">
                            @unless ($m->read_at)<x-badge tone="accent">New</x-badge>@endunless
                            <span class="{{ $m->read_at ? '' : 'font-semibold' }}">{{ $m->subject ?: 'General inquiry' }}</span>
                        </span>
                        <span class="mt-0.5 block text-sm text-muted">{{ $m->name }} · {{ $m->email }}@if ($m->company) · {{ $m->company }}@endif</span>
                    </span>
                    <span class="text-xs text-muted">{{ $m->created_at->format('j M Y, H:i') }}</span>
                </button>
                @if ($openId === $m->id)
                    <div class="mt-4 space-y-3 rounded-sm bg-elevated p-4 text-sm">
                        <p class="whitespace-pre-line">{{ $m->message }}</p>
                        <p class="text-xs text-muted">@if ($m->phone)Phone {{ $m->phone }} · @endif IP {{ $m->ip_address ?? '—' }}@if ($m->user) · registered customer #{{ $m->user_id }}@endif</p>
                        <div class="flex flex-wrap gap-2">
                            <a href="mailto:{{ $m->email }}?subject={{ rawurlencode('Re: '.($m->subject ?: 'Your message to '.$brand['name'])) }}" class="btn btn-primary btn-sm"><x-icon name="mail" class="h-4 w-4"/> Reply by email</a>
                            <button type="button" wire:click="markUnread({{ $m->id }})" class="btn btn-ghost btn-sm">Mark unread</button>
                            @if ($canManage)<button type="button" wire:click="delete({{ $m->id }})" wire:confirm="Delete this message?" class="btn btn-ghost btn-sm text-danger"><x-icon name="trash" class="h-4 w-4"/> Delete</button>@endif
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <p class="px-5 py-10 text-center text-muted">No messages yet.</p>
        @endforelse
    </div>
    <div class="mt-6">{{ $messages->links() }}</div>
</div>
