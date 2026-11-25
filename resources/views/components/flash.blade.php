@php $messages = array_filter(['success' => session('status') ?? session('success'), 'danger' => session('error'), 'warning' => session('warning')]); @endphp
@foreach ($messages as $tone => $message)
    <div x-data="{ show: true }" x-show="show" x-transition.opacity role="status" class="alert badge-{{ $tone }} mb-6 items-center">
        <x-icon :name="$tone === 'success' ? 'check' : 'shield'" class="h-4 w-4 shrink-0"/>
        <p class="flex-1">{{ $message }}</p>
        <button type="button" @click="show = false" class="-mr-1 flex h-7 w-7 shrink-0 items-center justify-center rounded-full hover:bg-black/5" aria-label="Dismiss"><x-icon name="x" class="h-4 w-4"/></button>
    </div>
@endforeach
