@props(['href' => null, 'icon' => null, 'variant' => null])
@php($classes = ['flex w-full items-center gap-2.5 px-4 py-2 text-start text-sm hover:bg-slate-50', 'text-danger-700' => $variant === 'danger', 'text-slate-700' => $variant !== 'danger'])
@if ($href)
    <a href="{{ $href }}" role="menuitem" {{ $attributes->class($classes) }}>
        @if ($icon)<x-admin.icon :name="$icon" class="size-4" />@endif
        <span>{{ $slot }}</span>
    </a>
@else
    <button type="button" role="menuitem" data-dropdown-close {{ $attributes->class($classes) }}>
        @if ($icon)<x-admin.icon :name="$icon" class="size-4" />@endif
        <span>{{ $slot }}</span>
    </button>
@endif
