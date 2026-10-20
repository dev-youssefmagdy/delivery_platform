{{-- Shared create/edit fields. $model: ?\App\Models\VehicleCategory --}}
<x-admin.form.section :title="__('admin.vehicle_categories.sections.identity')">
    <x-admin.grid>
        @if ($model)
            <x-admin.form.input name="code_display" :label="__('admin.catalog.fields.code')" :value="$model->code" :hint="__('admin.catalog.hints.code')" dir="ltr" disabled />
        @else
            <x-admin.form.input name="code" :label="__('admin.catalog.fields.code')" :hint="__('admin.catalog.hints.code')" maxlength="40" pattern="[a-z][a-z0-9_]{1,39}" dir="ltr" required />
        @endif
        <x-admin.form.input name="sort_order" type="number" min="0" max="65535" :label="__('admin.catalog.fields.sort_order')" :value="$model?->sort_order ?? 0" :hint="__('admin.catalog.hints.sort_order')" />
        <x-admin.form.input name="icon" :label="__('admin.catalog.fields.icon')" :value="$model?->icon" :hint="__('admin.catalog.hints.icon')" maxlength="64" dir="ltr" />
        <x-admin.form.input name="map_marker" :label="__('admin.catalog.fields.map_marker')" :value="$model?->map_marker" maxlength="64" dir="ltr" />
    </x-admin.grid>
    <x-admin.form.translatable-input name="name" :label="__('admin.vehicle_categories.fields.name')" :value="$model?->getTranslations('name') ?? []" maxlength="191" required />
    <x-admin.form.translatable-textarea name="description" :label="__('admin.vehicle_categories.fields.description')" :value="$model?->getTranslations('description') ?? []" maxlength="1000" />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.vehicle_categories.sections.capacity')" :description="__('admin.vehicle_categories.sections.capacity_hint')">
    <x-admin.grid>
        <x-admin.form.input name="passenger_capacity" type="number" min="1" max="100" :label="__('admin.vehicle_categories.fields.passenger_capacity')" :value="$model?->passenger_capacity" />
        <x-admin.form.input name="luggage_capacity" type="number" min="0" max="100" :label="__('admin.vehicle_categories.fields.luggage_capacity')" :value="$model?->luggage_capacity" />
        <x-admin.form.input name="max_payload_kg" type="number" min="0" step="0.01" :label="__('admin.vehicle_categories.fields.max_payload_kg')" :value="$model?->max_payload_kg" />
        <x-admin.form.input name="max_volume_m3" type="number" min="0" step="0.01" :label="__('admin.vehicle_categories.fields.max_volume_m3')" :value="$model?->max_volume_m3" />
    </x-admin.grid>
</x-admin.form.section>
