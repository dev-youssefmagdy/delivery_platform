<div data-repeater-row class="flex flex-wrap items-start gap-3 rounded-(--radius-control) border border-slate-200 bg-slate-50/60 p-3">
    @foreach ($fields as $field)
        @php
            $fieldName = $name.'['.$index.']['.$field['name'].']';
            $type = $field['type'] ?? 'text';
            $extra = $field['attributes'] ?? [];
            $value = $row[$field['name']] ?? null;
        @endphp
        <div class="min-w-40 flex-1">
            @if ($type === 'select')
                <x-admin.form.select :name="$fieldName" :label="$field['label']" :options="$field['options'] ?? []" :value="$value" :required="$field['required'] ?? false" :attributes="new \Illuminate\View\ComponentAttributeBag($extra)" />
            @elseif ($type === 'checkbox')
                <div class="pt-7"><x-admin.form.checkbox :name="$fieldName" :label="$field['label']" :checked="(bool) $value" /></div>
            @else
                <x-admin.form.input :name="$fieldName" :label="$field['label']" :type="$type" :value="$value" :required="$field['required'] ?? false" :attributes="new \Illuminate\View\ComponentAttributeBag($extra)" />
            @endif
        </div>
    @endforeach
    <div class="pt-7">
        <x-admin.icon-button icon="trash" variant="danger" :label="__('admin.common.remove_row')" x-on:click="remove($el)" />
    </div>
</div>
