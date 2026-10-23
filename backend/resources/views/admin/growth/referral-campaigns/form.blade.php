@php
    $editing = $campaign !== null;
    $scope = app(\App\Support\Growth\ScopeOptions::class);
    $currency = $campaign?->currency ?? (string) app(\App\Services\Settings\SettingsService::class)->string(\App\Enums\Settings\SettingKey::DefaultCurrency);
    $title = $editing ? __('growth.referral.edit') : __('growth.referral.create');
@endphp
<x-admin.layouts.app :title="$title">
    <x-admin.page-header
        :title="$title"
        :breadcrumbs="[['label' => __('growth.referral.title'), 'url' => route('admin.referral-campaigns.index')], ['label' => $title]]"
    />

    <x-admin.alert type="info" class="mb-6">{{ __('growth.referral.form_notice') }}</x-admin.alert>

    <x-admin.card class="max-w-4xl">
        <x-admin.form :action="$editing ? route('admin.referral-campaigns.update', $campaign) : route('admin.referral-campaigns.store')" :method="$editing ? 'PUT' : 'POST'">
            @if ($editing)
                <input type="hidden" name="lock_version" value="{{ $campaign->lock_version }}">
            @endif
            <x-admin.grid>
                <x-admin.form.input name="name" :label="__('growth.fields.name')" :value="$campaign?->name" maxlength="120" required />
                <x-admin.form.input name="name_en" :label="__('growth.fields.name_en')" :value="$campaign?->name_en" maxlength="120" dir="ltr" />
                @if ($editing)
                    <x-admin.detail-list :cols="1"><x-admin.detail-list.item :label="__('growth.fields.audience')"><x-admin.badge :value="$campaign->audience" /></x-admin.detail-list.item></x-admin.detail-list>
                @else
                    <x-admin.form.enum-select name="audience" :label="__('growth.fields.audience')" :enum="\App\Enums\Growth\ReferralAudience::class" required />
                @endif
                <x-admin.form.datetime name="starts_at" :label="__('growth.fields.starts_at')" :value="$campaign?->starts_at" />
                <x-admin.form.datetime name="ends_at" :label="__('growth.fields.ends_at')" :value="$campaign?->ends_at" />
            </x-admin.grid>

            <x-admin.form.section :title="__('growth.referral.sections.rewards')" :description="__('growth.referral.sections.rewards_hint')" class="mt-6">
                <x-admin.grid>
                    <x-admin.form.money name="referrer_reward_minor" :label="__('growth.referral.fields.referrer_reward')" :value="$campaign?->referrer_reward_minor ?? 0" :currency="$currency" required />
                    <x-admin.form.money name="referee_reward_minor" :label="__('growth.referral.fields.referee_reward')" :value="$campaign?->referee_reward_minor ?? 0" :currency="$currency" required />
                    <x-admin.form.input name="milestone_jobs" type="number" min="1" max="50" :label="__('growth.referral.fields.milestone')" :hint="__('growth.referral.hints.milestone')" :value="$campaign?->milestone_jobs ?? 1" dir="ltr" required />
                    <x-admin.form.input name="credit_valid_days" type="number" min="1" max="365" :label="__('growth.referral.fields.valid_days')" :hint="__('growth.referral.hints.valid_days')" :value="$campaign?->credit_valid_days ?? 30" dir="ltr" required />
                </x-admin.grid>
            </x-admin.form.section>

            <x-admin.form.section :title="__('growth.referral.sections.limits')" :description="__('growth.referral.sections.limits_hint')" class="mt-6">
                <x-admin.grid>
                    <x-admin.form.input name="per_referrer_cap" type="number" min="1" max="1000" :label="__('growth.referral.fields.cap')" :hint="__('growth.referral.hints.cap')" :value="$campaign?->per_referrer_cap ?? 20" dir="ltr" required />
                    <x-admin.form.money name="budget_minor" :label="__('growth.fields.budget')" :hint="__('growth.referral.hints.budget')" :value="$campaign?->budget_minor ?? 0" :currency="$currency" required />
                    <x-admin.form.money name="min_order_minor" :label="__('growth.fields.min_order')" :hint="__('growth.referral.hints.min_order')" :value="$campaign?->min_order_minor" :currency="$currency" />
                    <x-admin.form.remote-select name="service_category_ids[]" :label="__('growth.fields.categories')" :hint="__('growth.fields.empty_means_all')" lookup="service-categories" :selected="$scope->categories($campaign?->service_category_ids)" multiple />
                    <x-admin.form.remote-select name="city_ids[]" :label="__('growth.fields.cities')" :hint="__('growth.fields.empty_means_all')" lookup="cities" :selected="$scope->cities($campaign?->city_ids)" multiple />
                </x-admin.grid>
            </x-admin.form.section>

            <x-admin.form.actions :cancel="route('admin.referral-campaigns.index')" />
        </x-admin.form>
    </x-admin.card>
</x-admin.layouts.app>
