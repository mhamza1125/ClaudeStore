<x-layouts.admin title="Audit log">
    <x-page-header title="Audit log" eyebrow="Security"/>
    <form method="get" class="mb-6 flex gap-3"><label for="aa" class="sr-only">Action prefix</label><input id="aa" name="action" value="{{ request('action') }}" class="input max-w-xs" placeholder="Action prefix, e.g. product."><button class="btn btn-secondary">Filter</button></form>
    <div class="card overflow-x-auto">
        <table class="table">
            <thead><tr><th>When</th><th>User</th><th>Action</th><th>Description</th><th>Changes</th><th>IP</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="whitespace-nowrap text-muted">{{ $log->created_at?->format('j M Y H:i') }}</td>
                    <td>{{ $log->user?->name ?? 'System' }}</td>
                    <td><code class="text-xs">{{ $log->action }}</code></td>
                    <td>{{ $log->description }}</td>
                    <td class="max-w-md text-xs text-muted">
                        @foreach ($log->changes ?? [] as $field => $change)
                            <div><span class="text-text">{{ $field }}</span>: {{ is_array($change) && array_key_exists('from', $change) ? json_encode($change['from']).' → '.json_encode($change['to']) : json_encode($change) }}</div>
                        @endforeach
                    </td>
                    <td class="text-muted">{{ $log->ip_address }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="py-10 text-center text-muted">No entries.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $logs->links() }}</div>
</x-layouts.admin>
