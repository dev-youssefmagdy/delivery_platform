@props(['name', 'label' => null, 'hint' => null, 'options' => [], 'enum' => null, 'value' => null, 'inline' => true])
{{-- Radio buttons from `options` [value => label] or the cases of `enum`. --}}
@php
    if ($enum !== null) {
        $options = [];
        foreach ($enum::cases() as $case) {
            $options[$case->value] = $case instanceof \App\Enums\Contracts\HasLabel ? $case->label() : (string) $case->value;
        }
    }
    $current = \App\Support\Admin\FieldName::value($name, $value);
    $current = (string) ($current instanceof \BackedEnum ? $current->value : $current);
    $groupId = \App\Support\Admin\FieldName::id($name);
    $required = (bool) $attributes->get('required', false);
@endphp
<x-admin.form.field :name="$name" :label="$label" :hint="$hint" :id="$groupId" :required="$required" :label-for="false">
    <div role="radiogroup" aria-labelledby="{{ $groupId }}-label" @class(['flex flex-wrap gap-x-6 gap-y-2' => $inline, 'space-y-2' => ! $inline])>
        @foreach ($options as $optionValue => $optionLabel)
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="radio" name="{{ $name }}" value="{{ $optionValue }}" @checked($current === (string) $optionValue) {{ $attributes->class(['form-check rounded-full']) }}>
                <span>{{ $optionLabel }}</span>
            </label>
        @endforeach
    </div>
</x-admin.form.field>
