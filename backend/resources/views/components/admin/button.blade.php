@props(['variant' => 'primary', 'size' => null, 'icon' => null, 'href' => null, 'type' => 'button', 'loading' => false])
{{-- Variants: primary | secondary | danger | success | warning | ghost | link. Shows a spinner while [data-loading]. --}}
@php
    $classes = ['btn', 'btn-'.$variant, 'btn-'.$size => $size !== null];
@endphp
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>
        @if ($icon)
            <x-admin.icon :name="$icon" class="btn-icon size-4" />
        @endif
        <span class="btn-label">{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }} @if ($loading) data-loading disabled @endif>
        <x-admin.spinner class="btn-spinner" />
        @if ($icon)
            <x-admin.icon :name="$icon" class="btn-icon size-4" />
        @endif
        <span class="btn-label">{{ $slot }}</span>
    </button>
@endif
