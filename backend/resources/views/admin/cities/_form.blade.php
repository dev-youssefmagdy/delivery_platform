{{-- Shared create/edit fields. $model: ?\App\Models\City --}}
<x-admin.form.section :title="__('admin.cities.sections.basic')">
    <x-admin.grid>
        <x-admin.form.remote-select name="country_id" :label="__('admin.cities.fields.country')" lookup="countries" :selected="$model ? [$model->country_id => $model->country->name.' ('.$model->country->iso2.')'] : []" required />
        <x-admin.form.input name="timezone" :label="__('admin.cities.fields.timezone')" :value="$model?->timezone ?? config('admin.display_timezone')" :hint="__('admin.cities.hints.timezone')" maxlength="64" dir="ltr" required />
    </x-admin.grid>
    <x-admin.form.translatable-input name="name" :label="__('admin.cities.fields.name')" :value="$model?->getTranslations('name') ?? []" maxlength="191" required />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.cities.sections.location')">
    <x-admin.form.map-point
        lat-name="center_lat"
        lng-name="center_lng"
        :lat="$model?->center->lat"
        :lng="$model?->center->lng"
        :label="__('admin.cities.hints.center')"
        required
    />
</x-admin.form.section>
