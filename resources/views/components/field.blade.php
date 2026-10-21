@props(['label' => null, 'name', 'type' => 'text', 'help' => null, 'required' => false, 'value' => null, 'model' => null])
@php $id = $attributes->get('id', 'f_'.str_replace(['.', '[', ']'], '_', $name)); $errorKey = $model ?? $name; @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="field-label">{{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    @endif
    <input id="{{ $id }}" type="{{ $type }}" @if (! $model) name="{{ $name }}" value="{{ $type === 'password' ? '' : old($name, $value) }}" @endif
        @if ($model) wire:model.blur="{{ $model }}" @endif
        @if ($required) required @endif
        @error($errorKey) aria-invalid="true" aria-describedby="{{ $id }}_error" @enderror
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($errors->has($errorKey) ? ' input-invalid' : '')]) }}>
    @if ($help)<p class="field-help">{{ $help }}</p>@endif
    @error($errorKey)<p id="{{ $id }}_error" class="field-error">{{ $message }}</p>@enderror
</div>
