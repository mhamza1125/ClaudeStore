@props(['label' => null, 'name', 'rows' => 4, 'required' => false, 'value' => null, 'model' => null, 'help' => null])
@php $id = $attributes->get('id', 'f_'.str_replace(['.', '[', ']'], '_', $name)); $errorKey = $model ?? $name; @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="field-label">{{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    @endif
    <textarea id="{{ $id }}" rows="{{ $rows }}" @if ($model) wire:model.blur="{{ $model }}" @else name="{{ $name }}" @endif @if ($required) required @endif
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($errors->has($errorKey) ? ' input-invalid' : '')]) }}>@unless ($model){{ old($name, $value) }}@endunless</textarea>
    @if ($help)<p class="field-help">{{ $help }}</p>@endif
    @error($errorKey)<p class="field-error">{{ $message }}</p>@enderror
</div>
