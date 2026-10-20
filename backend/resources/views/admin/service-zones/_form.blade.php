{{-- Shared create/edit fields. $model: ?\App\Models\ServiceZone --}}
@php
    $hours = old('operating_hours', $model?->operating_hours ?? []);
@endphp
<x-admin.form.section :title="__('admin.service_zones.sections.basic')">
    <x-admin.form.remote-select name="city_id" :label="__('admin.service_zones.fields.city')" lookup="cities" :selected="$model ? [$model->city_id => $model->city->name] : []" required />
    <x-admin.form.translatable-input name="name" :label="__('admin.service_zones.fields.name')" :value="$model?->getTranslations('name') ?? []" maxlength="191" required />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.service_zones.sections.area')" :description="__('admin.service_zones.legal_hint')">
    <x-admin.form.polygon name="polygon" :label="__('admin.service_zones.fields.polygon')" :value="$model ? json_encode($model->polygon->toArray()) : null" required />
    <div
        class="mt-3"
        data-zone-overlap
        data-url="{{ route('admin.service-zones.overlaps') }}"
        data-input="f-polygon"
        data-zone-id="{{ $model?->id }}"
        data-label="{{ __('admin.service_zones.overlap_warning_live') }}"
        role="status"
        aria-live="polite"
    ></div>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.service_zones.sections.hours')" :description="__('admin.service_zones.hints.hours')">
    <x-admin.form.repeater
        name="operating_hours"
        :items="$hours"
        :max="28"
        :fields="[
            ['name' => 'day', 'label' => __('admin.service_zones.fields.hours_day'), 'type' => 'select', 'options' => \App\Enums\Catalog\Weekday::options(), 'required' => true],
            ['name' => 'from', 'label' => __('admin.service_zones.fields.hours_from'), 'type' => 'time', 'required' => true, 'attributes' => ['dir' => 'ltr']],
            ['name' => 'to', 'label' => __('admin.service_zones.fields.hours_to'), 'type' => 'time', 'required' => true, 'attributes' => ['dir' => 'ltr']],
        ]"
    />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.service_zones.sections.rules')">
    <x-admin.form.toggle name="restricted_pickup" :label="__('admin.service_zones.fields.restricted_pickup')" :hint="__('admin.service_zones.hints.restricted_pickup')" :checked="$model?->restricted_pickup ?? false" />
    <x-admin.form.textarea name="notes" :label="__('admin.service_zones.fields.notes')" :value="$model?->notes" maxlength="2000" />
</x-admin.form.section>
