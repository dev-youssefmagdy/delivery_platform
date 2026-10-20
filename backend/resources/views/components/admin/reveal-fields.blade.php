@props(['url', 'label' => null])
{{--
    Wraps masked values ([data-reveal-field="key"] elements). The button asks the server (audited, permissioned) for
    the real values and swaps them in; the values are not in the page until then.
--}}
<div {{ $attributes }} x-data="adminReveal(@js($url))">
    {{ $slot }}
    <div class="mt-3" x-show="!revealed">
        <x-admin.button variant="secondary" size="sm" icon="eye" x-on:click="reveal()" x-bind:disabled="loading" x-bind:data-loading="loading ? '' : null">{{ $label ?? __('admin.customers.reveal.button') }}</x-admin.button>
    </div>
</div>
