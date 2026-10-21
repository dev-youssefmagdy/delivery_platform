{{-- Shared create/edit fields. $model: ?\App\Models\CommissionRule (with currentVersion when editing) --}}
@php
    $currencyCode = $model?->currency ?? (string) app(\App\Services\Settings\SettingsService::class)->string(\App\Enums\Settings\SettingKey::DefaultCurrency);
    $version = $model?->currentVersion;
    $percent = $version ? rtrim(rtrim(number_format($version->percent_bp / 100, 2, '.', ''), '0'), '.') : '10';
@endphp
<x-admin.form.section :title="__('admin.commission_rules.sections.scope')" :description="__('admin.commission_rules.sections.scope_hint')">
    <x-admin.grid>
        <x-admin.form.input name="name" :label="__('admin.commission_rules.fields.name')" :value="$model?->name" maxlength="120" required />
        @if ($model)
            <x-admin.form.input name="service_display" :label="__('admin.commission_rules.fields.service')" :value="$model->serviceCategory?->name ?? __('admin.commission_rules.all_services')" disabled />
            <x-admin.form.input name="zone_display" :label="__('admin.commission_rules.fields.zone')" :value="$model->serviceZone?->name ?? __('admin.commission_rules.all_zones')" disabled />
            <x-admin.form.input name="tier_display" :label="__('admin.commission_rules.fields.provider_tier')" :value="$model->provider_tier ?? '—'" dir="ltr" disabled />
            <x-admin.form.input name="campaign_display" :label="__('admin.commission_rules.fields.campaign')" :value="$model->campaign_code ?? '—'" dir="ltr" disabled />
        @else
            <x-admin.form.remote-select name="service_category_id" :label="__('admin.commission_rules.fields.service')" :hint="__('admin.commission_rules.hints.service')" lookup="service-categories" />
            <x-admin.form.remote-select name="service_zone_id" :label="__('admin.commission_rules.fields.zone')" :hint="__('admin.commission_rules.hints.zone')" lookup="zones" />
            <x-admin.form.input name="provider_tier" :label="__('admin.commission_rules.fields.provider_tier')" :hint="__('admin.commission_rules.hints.provider_tier')" maxlength="40" dir="ltr" />
            <x-admin.form.input name="campaign_code" :label="__('admin.commission_rules.fields.campaign')" :hint="__('admin.commission_rules.hints.campaign')" maxlength="40" dir="ltr" />
        @endif
        <x-admin.form.input name="currency_display" :label="__('admin.commission_rules.fields.currency')" :value="$currencyCode" dir="ltr" disabled />
        <x-admin.form.input name="priority" type="number" step="1" min="-1000" max="1000" :label="__('admin.commission_rules.fields.priority')" :hint="__('admin.commission_rules.hints.priority')" :value="$model?->priority ?? 0" dir="ltr" required />
    </x-admin.grid>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.commission_rules.sections.period')" :description="__('admin.commission_rules.sections.period_hint')">
    <x-admin.grid>
        <x-admin.form.datetime name="effective_from" :label="__('admin.commission_rules.fields.effective_from')" :value="$model?->effective_from ?? now()" required />
        <x-admin.form.datetime name="effective_to" :label="__('admin.commission_rules.fields.effective_to')" :hint="__('admin.commission_rules.hints.effective_to')" :value="$model?->effective_to" />
    </x-admin.grid>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.commission_rules.sections.terms')" :description="__('admin.commission_rules.sections.terms_hint')">
    <x-admin.grid>
        <x-admin.form.enum-select name="type" :label="__('admin.commission_rules.fields.type')" :enum="\App\Enums\Finance\CommissionType::class" :value="$version?->type ?? \App\Enums\Finance\CommissionType::Percentage" required />
        <x-admin.form.input name="percent" type="number" step="0.01" min="0" max="100" suffix="%" :label="__('admin.commission_rules.fields.percent')" :hint="__('admin.commission_rules.hints.percent')" :value="$percent" dir="ltr" />
        <x-admin.form.money name="fixed_minor" :label="__('admin.commission_rules.fields.fixed')" :hint="__('admin.commission_rules.hints.fixed')" :value="$version?->fixed_minor ?? 0" :currency="$currencyCode" />
        <x-admin.form.money name="min_minor" :label="__('admin.commission_rules.fields.min')" :hint="__('admin.commission_rules.hints.min')" :value="$version?->min_minor" :currency="$currencyCode" />
        <x-admin.form.money name="max_minor" :label="__('admin.commission_rules.fields.max')" :hint="__('admin.commission_rules.hints.max')" :value="$version?->max_minor" :currency="$currencyCode" />
    </x-admin.grid>
    <div class="mt-4">
        <x-admin.form.toggle name="commission_on_tips" :label="__('admin.commission_rules.fields.on_tips')" :hint="__('admin.commission_rules.hints.on_tips')" :checked="$version?->commission_on_tips ?? false" />
    </div>
    <x-admin.alert type="info" class="mt-4">{{ __('admin.commission_rules.base_notice') }}</x-admin.alert>
</x-admin.form.section>

<x-admin.form.section :title="__('admin.commission_rules.sections.notes')">
    @if ($model)
        <x-admin.form.input name="reason" :label="__('admin.commission_rules.fields.change_reason')" :hint="__('admin.commission_rules.hints.change_reason')" maxlength="255" />
    @endif
    <x-admin.form.textarea name="notes" :label="__('admin.commission_rules.fields.notes')" :value="$model?->notes" maxlength="2000" />
</x-admin.form.section>
