{{-- Shared create/edit fields. $model: ?\App\Models\Coupon (with scopes when editing) --}}
@php
    $settings = app(\App\Services\Settings\SettingsService::class);
    $currencyCode = $model?->currency ?? (string) $settings->string(\App\Enums\Settings\SettingKey::DefaultCurrency);
    $presenter = app(\App\Support\Coupon\CouponPresenter::class);
    $selected = $model ? $presenter->selectedOptions($model) : [];
    $pick = static fn (string $key): array => $selected[$key] ?? [];
    $percent = $model && $model->discount_type === \App\Enums\Coupon\DiscountType::Percentage ? rtrim(rtrim(number_format($model->discount_value / 100, 2, '.', ''), '0'), '.') : '';
    $fixed = $model && $model->discount_type === \App\Enums\Coupon\DiscountType::Fixed ? $model->discount_value : null;
    $split = (array) ($model?->funding_split ?? ['platform_bp' => 10000, 'provider_bp' => 0, 'partner_bp' => 0]);
    $pct = static fn (int $bp): string => rtrim(rtrim(number_format($bp / 100, 2, '.', ''), '0'), '.') ?: '0';
    $timezone = $model?->timezone ?? (string) config('admin.display_timezone', 'UTC');
    $zones = array_combine(\DateTimeZone::listIdentifiers(), \DateTimeZone::listIdentifiers());
    $schedule = old('schedule', $model?->schedule ?? []);
    $isLive = (bool) $model?->status->isLive();
@endphp
<div x-data="{ type: '{{ old('discount_type', $model?->discount_type?->value ?? 'percentage') }}', funding: '{{ old('funding_source', $model?->funding_source?->value ?? 'platform') }}' }">
    <x-admin.form.section :title="__('admin.coupons.sections.identity')" :description="__('admin.coupons.sections.identity_hint')">
        <x-admin.grid>
            <x-admin.form.input name="name" :label="__('admin.coupons.fields.name')" :hint="__('admin.coupons.hints.name')" :value="$model?->name" maxlength="120" required />
            @if ($model)
                <x-admin.form.input name="code_display" :label="__('admin.coupons.fields.code')" :value="$model->code ?? __('admin.coupons.automatic')" dir="ltr" disabled />
            @else
                <x-admin.form.input name="code" :label="__('admin.coupons.fields.code')" :hint="__('admin.coupons.hints.code')" maxlength="40" dir="ltr" style="text-transform: uppercase" />
            @endif
            <x-admin.form.translatable-input name="title" :label="__('admin.coupons.fields.title')" :value="$model?->getTranslations('title') ?? []" maxlength="191" required />
            <x-admin.form.translatable-textarea name="description" :label="__('admin.coupons.fields.description')" :value="$model?->getTranslations('description') ?? []" maxlength="1000" />
            <x-admin.form.translatable-textarea name="legal_terms" :label="__('admin.coupons.fields.legal_terms')" :hint="__('admin.coupons.hints.legal_terms')" :value="$model?->getTranslations('legal_terms') ?? []" maxlength="4000" />
            <x-admin.form.input name="priority" type="number" step="1" min="-1000" max="1000" :label="__('admin.coupons.fields.priority')" :hint="__('admin.coupons.hints.priority')" :value="$model?->priority ?? 0" dir="ltr" />
        </x-admin.grid>
    </x-admin.form.section>

    <x-admin.form.section :title="__('admin.coupons.sections.discount')" :description="__('admin.coupons.sections.discount_hint')" class="mt-6">
        <x-admin.grid>
            <x-admin.form.enum-select name="discount_type" :label="__('admin.coupons.fields.discount_type')" :enum="\App\Enums\Coupon\DiscountType::class" :value="$model?->discount_type ?? \App\Enums\Coupon\DiscountType::Percentage" x-model="type" required />
            <div x-show="type === 'percentage'">
                <x-admin.form.input name="percent" type="number" step="0.01" min="0.01" max="100" suffix="%" :label="__('admin.coupons.fields.percent')" :value="$percent" dir="ltr" />
            </div>
            <div x-show="type === 'fixed'" x-cloak>
                <x-admin.form.money name="fixed_minor" :label="__('admin.coupons.fields.fixed')" :value="$fixed" :currency="$currencyCode" />
            </div>
            <div x-show="type === 'percentage'">
                <x-admin.form.money name="max_discount_minor" :label="__('admin.coupons.fields.max_discount')" :hint="__('admin.coupons.hints.max_discount')" :value="$model?->max_discount_minor" :currency="$currencyCode" />
            </div>
            <x-admin.form.money name="min_fare_minor" :label="__('admin.coupons.fields.min_fare')" :hint="__('admin.coupons.hints.min_fare')" :value="$model?->min_fare_minor" :currency="$currencyCode" />
        </x-admin.grid>
        <x-admin.alert type="info" class="mt-4">{{ __('admin.coupons.base_notice') }}</x-admin.alert>
    </x-admin.form.section>

    <x-admin.form.section :title="__('admin.coupons.sections.funding')" :description="__('admin.coupons.sections.funding_hint')" class="mt-6">
        <x-admin.grid>
            <x-admin.form.enum-select name="funding_source" :label="__('admin.coupons.fields.funding_source')" :enum="\App\Enums\Coupon\FundingSource::class" :value="$model?->funding_source ?? \App\Enums\Coupon\FundingSource::Platform" x-model="funding" required />
        </x-admin.grid>
        <div x-show="funding === 'split'" x-cloak class="mt-4">
            <x-admin.grid>
                <x-admin.form.input name="split_platform" type="number" step="0.01" min="0" max="100" suffix="%" :label="__('admin.coupons.fields.split_platform')" :value="$pct((int) ($split['platform_bp'] ?? 0))" dir="ltr" />
                <x-admin.form.input name="split_provider" type="number" step="0.01" min="0" max="100" suffix="%" :label="__('admin.coupons.fields.split_provider')" :value="$pct((int) ($split['provider_bp'] ?? 0))" dir="ltr" />
                <x-admin.form.input name="split_partner" type="number" step="0.01" min="0" max="100" suffix="%" :label="__('admin.coupons.fields.split_partner')" :value="$pct((int) ($split['partner_bp'] ?? 0))" dir="ltr" />
            </x-admin.grid>
        </div>
        <div class="mt-4 space-y-4">
            <x-admin.form.toggle name="restore_on_refund" :label="__('admin.coupons.fields.restore_on_refund')" :hint="__('admin.coupons.hints.restore_on_refund')" :checked="$model?->restore_on_refund ?? true" />
            <x-admin.form.toggle name="stackable" :label="__('admin.coupons.fields.stackable')" :hint="__('admin.coupons.hints.stackable')" :checked="$model?->stackable ?? false" />
        </div>
    </x-admin.form.section>

    <x-admin.form.section :title="__('admin.coupons.sections.limits')" :description="__('admin.coupons.sections.limits_hint')" class="mt-6">
        <x-admin.grid>
            <x-admin.form.input name="per_customer_limit" type="number" min="1" :label="__('admin.coupons.fields.per_customer_limit')" :hint="__('admin.coupons.hints.per_customer_limit')" :value="$model?->per_customer_limit" dir="ltr" />
            <x-admin.form.input name="global_limit" type="number" min="1" :label="__('admin.coupons.fields.global_limit')" :hint="__('admin.coupons.hints.global_limit')" :value="$model?->global_limit" dir="ltr" />
            <x-admin.form.money name="budget_minor" :label="__('admin.coupons.fields.budget')" :hint="__('admin.coupons.hints.budget')" :value="$model?->budget_minor" :currency="$currencyCode" />
        </x-admin.grid>
    </x-admin.form.section>

    <x-admin.form.section :title="__('admin.coupons.sections.eligibility')" :description="__('admin.coupons.sections.eligibility_hint')" class="mt-6">
        <p class="mb-4 text-sm text-slate-500">{{ __('admin.coupons.hints.scope_empty') }}</p>
        <x-admin.grid>
            <x-admin.form.remote-select name="family_ids[]" :label="__('admin.coupons.fields.family_ids')" lookup="service-families" :selected="$pick('family_ids')" multiple />
            <x-admin.form.remote-select name="category_ids[]" :label="__('admin.coupons.fields.category_ids')" lookup="service-categories" :selected="$pick('category_ids')" multiple />
            <x-admin.form.remote-select name="vehicle_category_ids[]" :label="__('admin.coupons.fields.vehicle_category_ids')" lookup="vehicle-categories" :selected="$pick('vehicle_category_ids')" multiple />
            <x-admin.form.remote-select name="city_ids[]" :label="__('admin.coupons.fields.city_ids')" lookup="cities" :selected="$pick('city_ids')" multiple />
            <x-admin.form.remote-select name="zone_ids[]" :label="__('admin.coupons.fields.zone_ids')" lookup="zones" :selected="$pick('zone_ids')" multiple />
            <x-admin.form.remote-select name="excluded_category_ids[]" :label="__('admin.coupons.fields.excluded_category_ids')" :hint="__('admin.coupons.hints.excluded')" lookup="service-categories" :selected="$pick('excluded_category_ids')" multiple />
            <x-admin.form.remote-select name="excluded_vehicle_category_ids[]" :label="__('admin.coupons.fields.excluded_vehicle_category_ids')" lookup="vehicle-categories" :selected="$pick('excluded_vehicle_category_ids')" multiple />
            <x-admin.form.select name="segments[]" :label="__('admin.coupons.fields.segments')" :hint="__('admin.coupons.hints.segments')" :options="\App\Enums\Coupon\CouponSegment::options()" :value="$model?->segments ?? []" multiple />
            <x-admin.form.select name="payment_methods[]" :label="__('admin.coupons.fields.payment_methods')" :hint="__('admin.coupons.hints.payment_methods')" :options="\App\Enums\Payments\PaymentMethodType::options()" :value="$model?->payment_methods ?? []" multiple />
        </x-admin.grid>
        <div class="mt-6">
            <x-admin.form.repeater
                name="schedule"
                :label="__('admin.coupons.fields.schedule')"
                :hint="__('admin.coupons.hints.schedule')"
                :items="$schedule"
                :max="28"
                :fields="[
                    ['name' => 'day', 'label' => __('admin.service_categories.fields.hours_day'), 'type' => 'select', 'options' => \App\Enums\Catalog\Weekday::options(), 'required' => true],
                    ['name' => 'from', 'label' => __('admin.service_categories.fields.hours_from'), 'type' => 'time', 'required' => true, 'attributes' => ['dir' => 'ltr']],
                    ['name' => 'to', 'label' => __('admin.service_categories.fields.hours_to'), 'type' => 'time', 'required' => true, 'attributes' => ['dir' => 'ltr']],
                ]"
            />
        </div>
    </x-admin.form.section>

    <x-admin.form.section :title="__('admin.coupons.sections.period')" :description="__('admin.coupons.sections.period_hint')" class="mt-6">
        <x-admin.grid>
            <x-admin.form.select name="timezone" :label="__('admin.coupons.fields.timezone')" :hint="__('admin.coupons.hints.timezone')" :options="$zones" :value="$timezone" searchable required />
            <span></span>
            <x-admin.form.datetime name="starts_at" :label="__('admin.coupons.fields.starts_at')" :hint="__('admin.coupons.hints.starts_at')" :value="$model?->starts_at" />
            <x-admin.form.datetime name="ends_at" :label="__('admin.coupons.fields.ends_at')" :hint="__('admin.coupons.hints.ends_at')" :value="$model?->ends_at" />
        </x-admin.grid>
    </x-admin.form.section>

    @if ($model)
        <x-admin.form.section :title="__('admin.coupons.sections.change')" class="mt-6">
            <x-admin.form.input name="reason" :label="__('admin.coupons.fields.change_reason')" :hint="$isLive ? __('admin.coupons.hints.change_reason_live') : __('admin.coupons.hints.change_reason')" maxlength="255" />
        </x-admin.form.section>
    @endif
</div>
