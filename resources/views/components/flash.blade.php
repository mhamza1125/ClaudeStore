@php $messages = array_filter(['success' => session('status') ?? session('success'), 'danger' => session('error'), 'warning' => session('warning')]); @endphp
@foreach ($messages as $tone => $message)
    <div x-data="{ show: true }" x-show="show" role="status" class="mb-6 flex items-start justify-between gap-4 rounded-sm border px-4 py-3 text-sm badge-{{ $tone }} border-current/20">
        <p>{{ $message }}</p>
        <button type="button" @click="show = false" class="shrink-0" aria-label="Dismiss"><x-icon name="x" class="h-4 w-4"/></button>
    </div>
@endforeach
