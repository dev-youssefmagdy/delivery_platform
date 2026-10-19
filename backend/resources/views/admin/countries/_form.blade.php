{{-- Shared create/edit fields. $model: ?\App\Models\Country; $currencyOptions, $timezoneOptions: [value => label] --}}
<x-admin.form.section :title="__('admin.countries.sections.identity')">
    <x-admin.grid>
        @if ($model)
            <x-admin.form.input name="iso2_display" :label="__('admin.countries.fields.iso2')" :value="$model->iso2" :hint="__('admin.countries.hints.iso2_immutable')" dir="ltr" disabled />
        @else
            <x-admin.form.input name="iso2" :label="__('admin.countries.fields.iso2')" :hint="__('admin.countries.hints.iso2')" maxlength="2" minlength="2" pattern="[A-Za-z]{2}" dir="ltr" required />
        @endif
        <x-admin.form.input name="phone_code" :label="__('admin.countries.fields.phone_code')" :value="$model?->phone_code ?? '+'" :hint="__('admin.countries.hints.phone_code')" maxlength="5" pattern="\+[0-9]{1,4}" dir="ltr" required />
    </x-admin.grid>
    <x-admin.form.translatable-input name="name" :label="__('admin.countries.fields.name')" :value="$model?->getTranslations('name') ?? []" maxlength="191" required />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.countries.sections.market')" :description="__('admin.countries.sections.market_hint')">
    <x-admin.grid>
        <x-admin.form.select name="currency_code" :label="__('admin.countries.fields.currency')" :options="$currencyOptions" :value="$model?->currency_code" required />
        <x-admin.form.select name="timezone" :label="__('admin.countries.fields.timezone')" :options="$timezoneOptions" :value="$model?->timezone ?? config('app.timezone')" searchable required />
        <x-admin.form.enum-select name="default_locale" :label="__('admin.countries.fields.default_locale')" :enum="\App\Enums\Common\Locale::class" :value="$model?->default_locale ?? 'ar'" required />
    </x-admin.grid>
    <x-admin.form.toggle name="active" :label="__('admin.countries.fields.active')" :hint="__('admin.countries.hints.active')" :checked="$model?->active ?? true" />
</x-admin.form.section>
