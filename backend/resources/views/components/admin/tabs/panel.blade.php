@props(['id', 'url' => null])
<div
    {{ $attributes }}
    role="tabpanel"
    id="panel-{{ $id }}"
    aria-labelledby="tab-{{ $id }}"
    data-tab-panel="{{ $id }}"
    @if ($url) data-tab-url="{{ $url }}" @endif
    x-show="isActive(@js($id))"
    x-cloak
    tabindex="0"
>
    @if ($url && trim((string) $slot) === '')
        <x-admin.skeleton :lines="4" />
    @else
        {{ $slot }}
    @endif
</div>
