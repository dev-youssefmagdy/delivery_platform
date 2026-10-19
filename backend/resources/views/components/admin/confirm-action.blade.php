@props([
    'url',
    'method' => 'POST',
    'title' => null,
    'message' => null,
    'confirmLabel' => null,
    'reasonRequired' => false,
    'table' => null,
    'variant' => 'danger',
    'icon' => null,
    'iconOnly' => false,
])
{{--
    Dangerous action: opens the global confirm dialog; with reason-required the dialog shows a MANDATORY reason
    textarea. The request goes through the AJAX/Precognition protocol and reloads `table` on success.
--}}
@php
    $data = [
        'data-confirm-action' => true,
        'data-url' => $url,
        'data-method' => strtoupper($method),
        'data-title' => $title ?? __('admin.confirm.title'),
        'data-message' => $message ?? __('admin.confirm.message'),
        'data-confirm-label' => $confirmLabel ?? __('admin.confirm.confirm'),
        'data-reason-required' => $reasonRequired ? 'true' : 'false',
        'data-reload-table' => $table,
        'data-variant' => $variant,
    ];
@endphp
@if ($iconOnly)
    <x-admin.icon-button :icon="$icon ?? 'exclamation'" :label="trim((string) $slot)" :variant="$variant === 'danger' ? 'danger' : null" {{ $attributes->merge($data) }} />
@else
    <x-admin.button :variant="$variant" :icon="$icon" {{ $attributes->merge($data) }}>{{ $slot }}</x-admin.button>
@endif
