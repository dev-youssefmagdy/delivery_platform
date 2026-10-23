@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i') ?? '—';
    $scope = app(\App\Support\Growth\ScopeOptions::class);
@endphp
<x-admin.layouts.app :title="$campaign->displayName()">
    <x-admin.page-header
        :title="$campaign->displayName()"
        :subtitle="__('growth.referral.show_subtitle')"
        :breadcrumbs="[['label' => __('growth.referral.title'), 'url' => route('admin.referral-campaigns.index')], ['label' => $campaign->displayName()]]"
    >
        @can('update', $campaign)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.referral-campaigns.edit', $campaign)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @foreach ($campaign->status->next() as $target)
            @can('transition', [$campaign, $target])
                <x-admin.confirm-action
                    :url="route('admin.referral-campaigns.transition', [$campaign, 'status' => $target->value])"
                    :title="__('growth.confirm.title')"
                    :message="__('growth.confirm.message.'.$target->value)"
                    :confirm-label="__('growth.actions.'.$target->value)"
                    :variant="$target === \App\Enums\Growth\CampaignStatus::Archived ? 'danger' : 'secondary'"
                    :reason-required="$target->needsReason()"
                >{{ __('growth.actions.'.$target->value) }}</x-admin.confirm-action>
            @endcan
        @endforeach
    </x-admin.page-header>

    <div class="mb-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-admin.stat-tile :label="__('growth.referral.totals.relationships')" :value="number_format($totals['relationships'])" icon="users" />
        <x-admin.stat-tile :label="__('growth.referral.totals.rewarded')" :value="number_format($totals['rewarded'])" icon="check" tone="success" />
        <x-admin.stat-tile :label="__('growth.referral.totals.under_review')" :value="number_format($totals['under_review'])" icon="shield" tone="warning" :href="route('admin.referral-relationships.index', ['filters' => ['campaign_ids' => [$campaign->id], 'statuses' => ['under_review']]])" />
        <x-admin.stat-tile :label="__('growth.fields.budget_used')" :value="app(\App\Support\Money\MoneyFormatter::class)->format(\App\Support\Money\Money::of($campaign->spent_minor, app(\App\Support\Money\CurrencyRegistry::class)->get($campaign->currency))).' / '.app(\App\Support\Money\MoneyFormatter::class)->format(\App\Support\Money\Money::of($campaign->budget_minor, app(\App\Support\Money\CurrencyRegistry::class)->get($campaign->currency)))" icon="banknotes" tone="info" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card :title="__('admin.common.details')">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('growth.fields.audience')"><x-admin.badge :value="$campaign->audience" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('growth.fields.status')"><x-admin.badge :value="$campaign->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('growth.referral.fields.referrer_reward')"><x-admin.money :minor="$campaign->referrer_reward_minor" :currency="$campaign->currency" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('growth.referral.fields.referee_reward')"><x-admin.money :minor="$campaign->referee_reward_minor" :currency="$campaign->currency" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('growth.referral.fields.milestone')" :value="$campaign->milestone_jobs" />
                    <x-admin.detail-list.item :label="__('growth.referral.fields.valid_days')" :value="$campaign->audience === \App\Enums\Growth\ReferralAudience::Customer ? $campaign->credit_valid_days : __('growth.referral.no_expiry')" />
                    <x-admin.detail-list.item :label="__('growth.referral.fields.cap')" :value="$campaign->per_referrer_cap" />
                    <x-admin.detail-list.item :label="__('growth.fields.min_order')"><x-admin.money :minor="$campaign->min_order_minor" :currency="$campaign->currency" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('growth.fields.categories')" :value="implode('، ', $scope->categories($campaign->service_category_ids)) ?: __('growth.fields.all')" />
                    <x-admin.detail-list.item :label="__('growth.fields.cities')" :value="implode('، ', $scope->cities($campaign->city_ids)) ?: __('growth.fields.all')" />
                    <x-admin.detail-list.item :label="__('growth.fields.starts_at')" :value="$when($campaign->starts_at)" />
                    <x-admin.detail-list.item :label="__('growth.fields.ends_at')" :value="$when($campaign->ends_at)" />
                    <x-admin.detail-list.item :label="__('growth.fields.status_reason')" :value="$campaign->status_reason ?? '—'" />
                </x-admin.detail-list>
            </x-admin.card>

            @can('adjust', $campaign)
                <x-admin.card :title="__('growth.adjust.title')" :subtitle="__('growth.adjust.hint')">
                    <x-admin.form :action="route('admin.referral-campaigns.adjust', $campaign)" method="POST">
                        <x-admin.form.money name="budget_minor" :label="__('growth.fields.budget')" :value="$campaign->budget_minor" :currency="$campaign->currency" required />
                        <x-admin.form.textarea name="reason" :label="__('admin.common.reason')" rows="3" maxlength="1000" required />
                        <x-admin.form.actions />
                    </x-admin.form>
                </x-admin.card>
            @endcan

            <x-admin.card :title="__('admin.common.activity')">
                @include('admin.partials.activity', ['logs' => $logs])
            </x-admin.card>
        </div>
    </div>
</x-admin.layouts.app>
