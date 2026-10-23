@php
    $editing = $campaign !== null;
    $scope = app(\App\Support\Growth\ScopeOptions::class);
    $currency = $campaign?->currency ?? (string) app(\App\Services\Settings\SettingsService::class)->string(\App\Enums\Settings\SettingKey::DefaultCurrency);
    $title = $editing ? __('growth.incentive.edit') : __('growth.incentive.create');
@endphp
<x-admin.layouts.app :title="$title">
    <x-admin.page-header
        :title="$title"
        :breadcrumbs="[['label' => __('growth.incentive.title'), 'url' => route('admin.incentive-campaigns.index')], ['label' => $title]]"
    />

    <x-admin.alert type="info" class="mb-6">{{ __('growth.incentive.form_notice') }}</x-admin.alert>

    <x-admin.card class="max-w-4xl">
        <x-admin.form :action="$editing ? route('admin.incentive-campaigns.update', $campaign) : route('admin.incentive-campaigns.store')" :method="$editing ? 'PUT' : 'POST'">
            @if ($editing)
                <input type="hidden" name="lock_version" value="{{ $campaign->lock_version }}">
            @endif
            <x-admin.grid>
                <x-admin.form.input name="name" :label="__('growth.fields.name')" :value="$campaign?->name" maxlength="120" required />
                <x-admin.form.input name="name_en" :label="__('growth.fields.name_en')" :value="$campaign?->name_en" maxlength="120" dir="ltr" />
                <x-admin.form.datetime name="starts_at" :label="__('growth.fields.starts_at')" :value="$campaign?->starts_at" required />
                <x-admin.form.datetime name="ends_at" :label="__('growth.fields.ends_at')" :value="$campaign?->ends_at" required />
            </x-admin.grid>

            <x-admin.form.section :title="__('growth.incentive.sections.quest')" :description="__('growth.incentive.sections.quest_hint')" class="mt-6">
                <x-admin.grid>
                    <x-admin.form.input name="target_jobs" type="number" min="1" max="1000" :label="__('growth.incentive.fields.target')" :value="$campaign?->target_jobs ?? 10" dir="ltr" required />
                    <x-admin.form.money name="bonus_minor" :label="__('growth.incentive.fields.bonus')" :value="$campaign?->bonus_minor" :currency="$currency" required />
                    <x-admin.form.input name="max_awards_per_provider" type="number" min="1" max="100" :label="__('growth.incentive.fields.max_awards')" :hint="__('growth.incentive.hints.max_awards')" :value="$campaign?->max_awards_per_provider ?? 1" dir="ltr" required />
                    <x-admin.form.money name="budget_minor" :label="__('growth.fields.budget')" :hint="__('growth.incentive.hints.budget')" :value="$campaign?->budget_minor" :currency="$currency" required />
                </x-admin.grid>
            </x-admin.form.section>

            <x-admin.form.section :title="__('growth.incentive.sections.eligibility')" :description="__('growth.incentive.sections.eligibility_hint')" class="mt-6">
                <x-admin.grid>
                    <x-admin.form.input name="min_rating" type="number" step="0.01" min="0" max="5" :label="__('growth.incentive.fields.min_rating')" :value="$campaign?->min_rating" dir="ltr" />
                    <x-admin.form.money name="min_order_minor" :label="__('growth.fields.min_order')" :value="$campaign?->min_order_minor" :currency="$currency" />
                    <x-admin.form.remote-select name="service_category_ids[]" :label="__('growth.fields.categories')" :hint="__('growth.fields.empty_means_all')" lookup="service-categories" :selected="$scope->categories($campaign?->service_category_ids)" multiple />
                    <x-admin.form.remote-select name="city_ids[]" :label="__('growth.fields.cities')" :hint="__('growth.fields.empty_means_all')" lookup="cities" :selected="$scope->cities($campaign?->city_ids)" multiple />
                </x-admin.grid>
            </x-admin.form.section>

            <x-admin.form.section :title="__('growth.incentive.sections.fraud')" :description="__('growth.incentive.sections.fraud_hint')" class="mt-6">
                <x-admin.grid>
                    <x-admin.form.input name="max_jobs_per_customer" type="number" min="1" max="100" :label="__('growth.incentive.fields.max_per_customer')" :hint="__('growth.incentive.hints.max_per_customer')" :value="$campaign?->max_jobs_per_customer ?? 3" dir="ltr" required />
                </x-admin.grid>
            </x-admin.form.section>

            <x-admin.form.actions :cancel="route('admin.incentive-campaigns.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
