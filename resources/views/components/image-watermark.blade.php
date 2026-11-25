{{-- Centred watermark laid over a product image when Admin → B2B settings uses "overlay" mode
     (in "embedded" mode the mark is already in the served image and this renders nothing).
     Place inside a positioned (relative) wrapper around the image. --}}
@props(['size' => 'md'])
@php $wm = app(\App\Images\ProtectedImages::class)->overlay(auth()->user()); @endphp
@if ($wm)
    <span class="wm-overlay wm-{{ $size }}" style="opacity: {{ $wm['opacity'] / 100 }}" aria-hidden="true" data-testid="image-watermark">
        @if ($wm['company'])<span>{{ $wm['company'] }}</span>@endif
        @if ($wm['personal'])<span class="wm-personal">{{ $wm['personal'] }}</span>@endif
    </span>
@endif
