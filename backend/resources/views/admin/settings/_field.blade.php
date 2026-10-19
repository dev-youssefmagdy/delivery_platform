{{-- One setting rendered from SettingKey metadata. $field: \App\DTOs\Settings\SettingFieldData --}}
@php($input = $field->input)
@switch($input)
    @case(\App\Enums\Settings\SettingInput::Toggle)
        <x-admin.form.toggle :name="$field->name" :label="$field->label" :hint="$field->description" :checked="(bool) $field->value" />
        @break

    @case(\App\Enums\Settings\SettingInput::Select)
        <x-admin.form.select :name="$field->name" :label="$field->label" :hint="$field->description" :options="$field->options" :value="$field->value" :searchable="count($field->options) > 8" required />
        @break

    @case(\App\Enums\Settings\SettingInput::MultiSelect)
        <x-admin.form.select :name="$field->name" :label="$field->label" :hint="$field->description" :options="$field->options" :value="$field->value" multiple />
        @break

    @case(\App\Enums\Settings\SettingInput::Money)
        <x-admin.form.money :name="$field->name" :label="$field->label" :hint="$field->description" :value="(int) $field->value" required />
        @break

    @case(\App\Enums\Settings\SettingInput::Number)
        <x-admin.form.input type="number" :name="$field->name" :label="$field->label" :hint="$field->description" :value="$field->value" :suffix="$field->unit" :min="$field->bounds[0] ?? null" :max="$field->bounds[1] ?? null" step="1" dir="ltr" required wrapper-class="max-w-xs" />
        @break

    @case(\App\Enums\Settings\SettingInput::NumberList)
        <x-admin.form.input :name="$field->name" :label="$field->label" :hint="$field->description" :value="$field->displayValue()" :suffix="$field->unit" inputmode="decimal" dir="ltr" required wrapper-class="max-w-md" />
        @break

    @default
        <x-admin.form.input
            :type="match ($input) { \App\Enums\Settings\SettingInput::Email => 'email', \App\Enums\Settings\SettingInput::Phone => 'tel', default => 'text' }"
            :name="$field->name" :label="$field->label" :hint="$field->description" :value="$field->displayValue()"
            maxlength="191" dir="ltr" :required="$field->required" wrapper-class="max-w-md"
        />
@endswitch
