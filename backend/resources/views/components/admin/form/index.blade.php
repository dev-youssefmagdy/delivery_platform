@props([
    'action',
    'method' => 'POST',
    'liveValidation' => true,
    'confirm' => null,
    'confirmTitle' => null,
    'reloadTable' => null,
    'closeOnSuccess' => null,
])
{{--
    The AJAX + Precognition form (Plan §5.3), bound by resources/js/admin/core/ajax-form.js:
    client checks → server validation call (Precognition) → inline errors & blocked submit, or the
    `ajax-form:submit` event → real AJAX submit → AdminFormResponse instructions.
    confirm="…" asks before the real submit; reload-table="id" reloads a table on success.
--}}
@php
    $verb = strtoupper($method);
@endphp
<form
    action="{{ $action }}"
    method="POST"
    enctype="multipart/form-data"
    novalidate
    data-ajax-form
    data-live-validation="{{ $liveValidation ? 'true' : 'false' }}"
    @if ($confirm) data-confirm="{{ $confirm }}" @endif
    @if ($confirmTitle) data-confirm-title="{{ $confirmTitle }}" @endif
    @if ($reloadTable) data-reload-table="{{ $reloadTable }}" @endif
    @if ($closeOnSuccess !== null) data-close-on-success="{{ $closeOnSuccess ? 'true' : 'false' }}" @endif
    {{ $attributes }}
>
    @csrf
    @if ($verb !== 'POST')
        <input type="hidden" name="_method" value="{{ $verb }}">
    @endif

    <div data-error-summary-wrapper hidden class="mb-5">
        <x-admin.alert type="danger" :title="__('admin.form.errors_title')">
            <ul data-error-summary class="list-disc space-y-1 ps-5"></ul>
        </x-admin.alert>
    </div>

    {{ $slot }}
</form>
