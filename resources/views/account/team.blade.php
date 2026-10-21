<x-layouts.account title="Team">
    <x-page-header title="Team members" eyebrow="{{ $company?->name ?? 'Company' }}"/>
    @if (! $company)
        <x-empty title="No company profile yet" icon="users"><a class="link" href="{{ route('account.company') }}">Create your company profile</a> first.</x-empty>
    @else
        <div class="card overflow-x-auto">
            <table class="table">
                <thead><tr><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                <tbody>@foreach ($members as $m)<tr><td>{{ $m->name }}</td><td>{{ $m->email }}</td><td><x-badge :tone="$m->company_role === 'owner' ? 'accent' : 'neutral'">{{ ucfirst($m->company_role ?? 'member') }}</x-badge></td></tr>@endforeach</tbody>
            </table>
        </div>
        <p class="mt-4 text-sm text-muted">To add a colleague to your company account, ask our trade team at <a class="link" href="mailto:{{ setting('brand.email') }}">{{ setting('brand.email') }}</a>. Self-service invitations are not yet available.</p>
    @endif
</x-layouts.account>
