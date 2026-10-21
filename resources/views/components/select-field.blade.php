@props(['label' => null, 'name', 'options' => [], 'placeholder' => null, 'required' => false, 'value' => null, 'model' => null, 'help' => null])
@php $id = $attributes->get('id', 'f_'.str_replace(['.', '[', ']'], '_', $name)); $errorKey = $model ?? $name; $current = (string) old($name, $value); @endphp
<div {{ $attributes->only('class') }}>
    @if ($label)
        <label for="{{ $id }}" class="field-label">{{ $label }}@if ($required)<span class="text-danger" aria-hidden="true"> *</span>@endif</label>
    @endif
    <select id="{{ $id }}" @if ($model) wire:model.live="{{ $model }}" @else name="{{ $name }}" @endif @if ($required) required @endif
        {{ $attributes->except(['class', 'id'])->merge(['class' => 'input'.($errors->has($errorKey) ? ' input-invalid' : '')]) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $optValue => $optLabel)
            <option value="{{ $optValue }}" @selected(! $model && $current === (string) $optValue)>{{ $optLabel }}</option>
        @endforeach
    </select>
    @if ($help)<p class="field-help">{{ $help }}</p>@endif
    @error($errorKey)<p class="field-error">{{ $message }}</p>@enderror
</div>
