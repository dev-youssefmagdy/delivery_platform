{{-- Form-schema builder (repeater). $category, $draft (ServiceFormSchema), $builderRows (see FormSchemaBuilderMapper) --}}
@php
    $typeOptions = \App\Enums\Catalog\FormFieldType::options();
    $operatorOptions = \App\Enums\Catalog\VisibilityOperator::options();
    $s = static fn (string $key): string => __('admin.service_categories.schema.'.$key);
@endphp
<x-admin.form :action="route('admin.service-categories.form-schema.update', $category)" method="PUT">
    <input type="hidden" name="lock_version" value="{{ $draft->lock_version }}">
    <x-admin.form.section :title="$s('title')" :description="$s('hint')">
        @if ($builderRows === [])
            <x-admin.alert type="info" class="mb-4">{{ $s('empty') }}</x-admin.alert>
        @endif
        <x-admin.form.repeater
            name="fields"
            :items="$builderRows"
            :max="\App\Services\Catalog\Forms\FormSchemaValidator::MAX_FIELDS"
            :add-label="$s('add')"
            :hint="$s('range_hint')"
            :fields="[
                ['name' => 'key', 'label' => $s('key'), 'required' => true, 'attributes' => ['maxlength' => 40, 'dir' => 'ltr', 'pattern' => '[a-z][a-z0-9_]{0,39}']],
                ['name' => 'type', 'label' => $s('type'), 'type' => 'select', 'options' => $typeOptions, 'required' => true],
                ['name' => 'label_ar', 'label' => $s('label_ar'), 'required' => true, 'attributes' => ['maxlength' => 191]],
                ['name' => 'label_en', 'label' => $s('label_en'), 'attributes' => ['maxlength' => 191, 'dir' => 'ltr']],
                ['name' => 'help_ar', 'label' => $s('help_ar'), 'attributes' => ['maxlength' => 500]],
                ['name' => 'help_en', 'label' => $s('help_en'), 'attributes' => ['maxlength' => 500, 'dir' => 'ltr']],
                ['name' => 'required', 'label' => $s('required'), 'type' => 'checkbox', 'class' => 'min-w-24'],
                ['name' => 'min', 'label' => $s('min'), 'type' => 'number', 'class' => 'min-w-24 max-w-32', 'attributes' => ['step' => 'any', 'dir' => 'ltr']],
                ['name' => 'max', 'label' => $s('max'), 'type' => 'number', 'class' => 'min-w-24 max-w-32', 'attributes' => ['step' => 'any', 'dir' => 'ltr']],
                ['name' => 'options_text', 'label' => $s('options').' ('.$s('options_hint').')', 'type' => 'textarea', 'class' => 'min-w-full basis-full', 'attributes' => ['dir' => 'ltr', 'placeholder' => 'sofa|كنبة|Sofa']],
                ['name' => 'visible_field', 'label' => $s('visible_field'), 'attributes' => ['maxlength' => 40, 'dir' => 'ltr']],
                ['name' => 'visible_op', 'label' => $s('visible_op'), 'type' => 'select', 'options' => $operatorOptions],
                ['name' => 'visible_value', 'label' => $s('visible_value').' ('.$s('visible_hint').')', 'attributes' => ['maxlength' => 500, 'dir' => 'ltr']],
            ]"
        />
    </x-admin.form.section>
    <x-admin.form.actions :submit="__('admin.service_categories.actions.save_schema')" />
</x-admin.form>
