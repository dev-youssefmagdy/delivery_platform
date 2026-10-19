@props(['icon', 'label', 'href' => null, 'variant' => null, 'type' => 'button'])
{{-- Icon-only button: `label` is required (aria-label + tooltip). --}}
@php($classes = ['icon-btn', 'icon-btn-'.$variant => $variant !== null])
@if ($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }} aria-label="{{ $label }}" title="{{ $label }}">
        <x-admin.icon :name="$icon" class="size-4" />
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }} aria-label="{{ $label }}" title="{{ $label }}">
        <x-admin.icon :name="$icon" class="size-4" />
    </button>
@endif
