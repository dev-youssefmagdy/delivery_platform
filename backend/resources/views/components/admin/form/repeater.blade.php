@props(['name', 'label' => null, 'hint' => null, 'fields' => [], 'items' => [], 'min' => 0, 'max' => 50, 'addLabel' => null])
{{--
    Repeatable rows. fields: list of ['name' => 'label', 'label' => '…', 'type' => 'text|number|select|checkbox',
    'options' => [...], 'required' => bool, 'attributes' => [...]]. Submits name[<index>][<field>]; indexes are
    never reused so errors like items.3.label map to the right row.
--}}
@php
    $rows = array_values(is_array($items) ? $items : []);
    $renderRow = function (string|int $index, array $row) use ($name, $fields) {
        return view('components.admin.form.partials.repeater-row', ['name' => $name, 'fields' => $fields, 'index' => $index, 'row' => $row])->render();
    };
@endphp
<fieldset {{ $attributes->class(['min-w-0']) }} data-field="{{ \App\Support\Admin\FieldName::key($name) }}" x-data="adminRepeater({ next: {{ count($rows) }}, min: {{ (int) $min }}, max: {{ (int) $max }} })">
    @if ($label)
        <legend class="form-label">{{ $label }}</legend>
    @endif

    <div x-ref="rows" class="space-y-3">
        @foreach ($rows as $index => $row)
            {!! $renderRow($index, (array) $row) !!}
        @endforeach
    </div>

    <template x-ref="template">{!! $renderRow('__INDEX__', []) !!}</template>

    <div class="mt-3 flex items-center gap-3">
        <x-admin.button variant="secondary" size="sm" icon="plus" x-on:click="add()" x-bind:disabled="count >= max">{{ $addLabel ?? __('admin.common.add_row') }}</x-admin.button>
        @if ($hint)
            <p class="form-hint !mt-0">{{ $hint }}</p>
        @endif
    </div>
    <x-admin.form.field-error :for="\App\Support\Admin\FieldName::key($name)" />
</fieldset>
