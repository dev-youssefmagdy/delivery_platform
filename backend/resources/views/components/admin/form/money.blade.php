<x-admin.form.field :name="$name" :label="$label" :hint="$hint ?? __('admin.form.money_hint', ['currency' => $currencyCode])" :id="$id" :required="$required" x-data="adminMoney({{ $exponent }})">
    <div class="input-group" dir="ltr">
        <input
            type="text"
            inputmode="decimal"
            id="{{ $id }}"
            x-model="display"
            x-on:input="sync()"
            x-on:blur="sync()"
            data-error-key="{{ $key }}"
            @if ($required) required @endif
            aria-describedby="{{ $id }}-hint"
            {{ $attributes->class(['form-control text-end tabular-nums']) }}
        >
        <span class="input-addon">{{ $symbol }}</span>
    </div>
    <input type="hidden" name="{{ $name }}" value="{{ $minor }}" x-ref="minor">
</x-admin.form.field>
