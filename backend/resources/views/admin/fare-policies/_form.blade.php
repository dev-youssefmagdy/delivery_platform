{{-- Shared create/edit fields. $model: ?\App\Models\FarePolicy --}}
@php
    $currencyCode = $model?->currency ?? (string) app(\App\Services\Settings\SettingsService::class)->string(\App\Enums\Settings\SettingKey::DefaultCurrency);
    $currency = app(\App\Support\Money\CurrencyRegistry::class)->get($currencyCode);
    $decimal = static function (int $minor) use ($currency): string {
        if ($currency->exponent === 0) {
            return (string) $minor;
        }
        $digits = str_pad((string) $minor, $currency->exponent + 1, '0', STR_PAD_LEFT);

        return substr($digits, 0, -$currency->exponent).'.'.substr($digits, -$currency->exponent);
    };
    $surchargeRows = array_map(static fn (array $row): array => [
        'field' => $row['field'] ?? '',
        'mode' => $row['mode'] ?? 'fixed',
        'amount' => $decimal((int) ($row['amount_minor'] ?? 0)),
        'match' => $row['match'] ?? '',
        'label_ar' => $row['label']['ar'] ?? '',
        'label_en' => $row['label']['en'] ?? '',
    ], $model?->attribute_surcharges ?? []);
    $rows = old('surcharges', $surchargeRows);
@endphp
<x-admin.form.section :title="__('admin.fare_policies.sections.scope')" :description="__('admin.fare_policies.sections.scope_hint')">
    <x-admin.grid>
        @if ($model)
            <x-admin.form.input name="service_display" :label="__('admin.fare_policies.fields.service')" :value="$model->serviceCategory->name" disabled />
            <x-admin.form.input name="zone_display" :label="__('admin.fare_policies.fields.zone')" :value="$model->serviceZone?->name ?? __('admin.fare_policies.all_zones')" disabled />
        @else
            <x-admin.form.remote-select name="service_category_id" :label="__('admin.fare_policies.fields.service')" lookup="service-categories" required />
            <x-admin.form.remote-select name="service_zone_id" :label="__('admin.fare_policies.fields.zone')" :hint="__('admin.fare_policies.hints.zone')" lookup="zones" />
        @endif
        <x-admin.form.input name="currency_display" :label="__('admin.fare_policies.fields.currency')" :value="$currencyCode" dir="ltr" disabled />
        <x-admin.form.enum-select name="formula_version" :label="__('admin.fare_policies.fields.formula')" :hint="__('admin.fare_policies.hints.formula')" :enum="\App\Enums\Pricing\FareFormula::class" :value="$model?->formula_version ?? \App\Enums\Pricing\FareFormula::V1" required />
    </x-admin.grid>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.fare_policies.sections.period')" :description="__('admin.fare_policies.sections.period_hint')">
    <x-admin.grid>
        <x-admin.form.datetime name="effective_from" :label="__('admin.fare_policies.fields.effective_from')" :value="$model?->effective_from ?? now()" required />
        <x-admin.form.datetime name="effective_to" :label="__('admin.fare_policies.fields.effective_to')" :hint="__('admin.fare_policies.hints.effective_to')" :value="$model?->effective_to" />
    </x-admin.grid>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.fare_policies.sections.amounts')" :description="__('admin.fare_policies.sections.amounts_hint')">
    <x-admin.grid>
        <x-admin.form.money name="base_minor" :label="__('admin.fare_policies.fields.base')" :value="$model?->base_minor ?? 0" :currency="$currencyCode" required />
        <x-admin.form.money name="per_km_minor" :label="__('admin.fare_policies.fields.per_km')" :value="$model?->per_km_minor ?? 0" :currency="$currencyCode" required />
        <x-admin.form.money name="per_min_minor" :label="__('admin.fare_policies.fields.per_min')" :value="$model?->per_min_minor ?? 0" :currency="$currencyCode" required />
        <x-admin.form.money name="stop_fee_minor" :label="__('admin.fare_policies.fields.stop_fee')" :value="$model?->stop_fee_minor ?? 0" :currency="$currencyCode" required />
        <x-admin.form.money name="min_fare_minor" :label="__('admin.fare_policies.fields.min_fare')" :hint="__('admin.fare_policies.hints.min_fare')" :value="$model?->min_fare_minor ?? 0" :currency="$currencyCode" required />
        <x-admin.form.money name="rounding_step_minor" :label="__('admin.fare_policies.fields.rounding_step')" :hint="__('admin.fare_policies.hints.rounding_step')" :value="$model?->rounding_step_minor ?? 100" :currency="$currencyCode" required />
        <x-admin.form.input name="range_low_pct" type="number" step="0.01" min="1" max="100" suffix="%" :label="__('admin.fare_policies.fields.range_low')" :hint="__('admin.fare_policies.hints.range_low')" :value="$model ? rtrim(rtrim(number_format($model->range_low_bp / 100, 2, '.', ''), '0'), '.') : '85'" dir="ltr" required />
        <x-admin.form.input name="range_high_pct" type="number" step="0.01" min="100" max="10000" suffix="%" :label="__('admin.fare_policies.fields.range_high')" :hint="__('admin.fare_policies.hints.range_high')" :value="$model ? rtrim(rtrim(number_format($model->range_high_bp / 100, 2, '.', ''), '0'), '.') : '125'" dir="ltr" required />
    </x-admin.grid>
    <x-admin.alert type="info" class="mt-4">{{ __('admin.fare_policies.no_surge_notice') }}</x-admin.alert>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.fare_policies.sections.surcharges')" :description="__('admin.fare_policies.sections.surcharges_hint')">
    <x-admin.form.repeater
        name="surcharges"
        :items="$rows"
        :max="20"
        :add-label="__('admin.fare_policies.surcharges.add')"
        :fields="[
            ['name' => 'field', 'label' => __('admin.fare_policies.surcharges.field'), 'type' => 'text', 'attributes' => ['maxlength' => 40, 'dir' => 'ltr', 'pattern' => '[a-z][a-z0-9_]{0,39}']],
            ['name' => 'mode', 'label' => __('admin.fare_policies.surcharges.mode'), 'type' => 'select', 'options' => \App\Enums\Pricing\SurchargeMode::options(), 'required' => true],
            ['name' => 'amount', 'label' => __('admin.fare_policies.surcharges.amount').' ('.$currencyCode.')', 'type' => 'text', 'attributes' => ['inputmode' => 'decimal', 'dir' => 'ltr', 'maxlength' => 18]],
            ['name' => 'match', 'label' => __('admin.fare_policies.surcharges.match'), 'type' => 'text', 'attributes' => ['maxlength' => 100]],
            ['name' => 'label_ar', 'label' => __('admin.fare_policies.surcharges.label_ar'), 'type' => 'text', 'attributes' => ['maxlength' => 100]],
            ['name' => 'label_en', 'label' => __('admin.fare_policies.surcharges.label_en'), 'type' => 'text', 'attributes' => ['maxlength' => 100, 'dir' => 'ltr']],
        ]"
    />
</x-admin.form.section>

<x-admin.form.section :title="__('admin.fare_policies.sections.notes')">
    <x-admin.form.textarea name="notes" :label="__('admin.fare_policies.fields.notes')" :value="$model?->notes" maxlength="2000" />
</x-admin.form.section>
