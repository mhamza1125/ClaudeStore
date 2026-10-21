<div>
    <x-page-header title="Staff & roles" eyebrow="Access control"/>
    @if ($createdPassword)
        <div class="mb-6 panel text-sm"><p class="font-medium">Account created. Temporary password (shown once):</p><p class="mt-1 font-mono">{{ $createdPassword }}</p><p class="mt-1 text-muted">Share it securely; the user should change it from their profile, or use “Forgot password”.</p></div>
    @endif
    <div class="grid gap-6 xl:grid-cols-3">
        <div class="card overflow-x-auto xl:col-span-2">
            <table class="table">
                <thead><tr><th>Name</th><th>Role</th><th>Status</th><th>Last login</th><th></th></tr></thead>
                <tbody>
                @foreach ($staff as $u)
                    <tr>
                        <td>{{ $u->name }}<span class="block text-xs text-muted">{{ $u->email }}</span></td>
                        <td>
                            @if ($u->id === auth()->id())<x-badge tone="accent">{{ $u->getRoleNames()->first() }}</x-badge>
                            @else
                                <label class="sr-only" for="role{{ $u->id }}">Role</label>
                                <select id="role{{ $u->id }}" class="input py-1 text-xs" wire:change="setRole({{ $u->id }}, $event.target.value)">
                                    @foreach ($roles as $r)<option value="{{ $r }}" @selected($u->hasRole($r))>{{ $r }}</option>@endforeach
                                    <option value="customer">customer (remove staff access)</option>
                                </select>
                            @endif
                        </td>
                        <td>@if ($u->is_active)<x-badge tone="success">Active</x-badge>@else<x-badge tone="danger">Disabled</x-badge>@endif</td>
                        <td class="text-muted">{{ $u->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td>@if ($u->id !== auth()->id())<button type="button" wire:click="toggleActive({{ $u->id }})" class="btn btn-ghost btn-sm">{{ $u->is_active ? 'Disable' : 'Enable' }}</button>@endif</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <form wire:submit="create" class="panel space-y-4">
            <h2 class="text-2xl">Add staff member</h2>
            <x-field name="form.name" model="form.name" label="Name" required/>
            <x-field name="form.email" model="form.email" type="email" label="Email" required/>
            <x-select-field name="form.role" model="form.role" label="Role" :options="$roles"/>
            <p class="text-xs text-muted">super-admin: everything · admin: everything except staff · sales-rep: quotes assigned to them or unassigned.</p>
            <button class="btn btn-primary">Create account</button>
        </form>
    </div>
</div>
