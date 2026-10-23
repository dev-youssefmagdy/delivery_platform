@php
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i') ?? '—';
    $scope = app(\App\Support\Growth\ScopeOptions::class);
    $formatter = app(\App\Support\Money\MoneyFormatter::class);
    $currencies = app(\App\Support\Money\CurrencyRegistry::class);
    $money = static fn (int $minor): string => $formatter->format(\App\Support\Money\Money::of($minor, $currencies->get($campaign->currency)));
@endphp
<x-admin.layouts.app :title="$campaign->displayName()">
    <x-admin.page-header
        :title="$campaign->displayName()"
        :subtitle="__('growth.incentive.show_subtitle')"
        :breadcrumbs="[['label' => __('growth.incentive.title'), 'url' => route('admin.incentive-campaigns.index')], ['label' => $campaign->displayName()]]"
    >
        @can('update', $campaign)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.incentive-campaigns.edit', $campaign)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @foreach ($campaign->status->next() as $target)
            @can('transition', [$campaign, $target])
                <x-admin.confirm-action
                    :url="route('admin.incentive-campaigns.transition', [$campaign, 'status' => $target->value])"
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
        <x-admin.stat-tile :label="__('growth.incentive.totals.providers')" :value="number_format($totals['providers'])" icon="users" />
        <x-admin.stat-tile :label="__('growth.incentive.totals.awards')" :value="number_format($totals['awards'])" icon="check" tone="success" />
        <x-admin.stat-tile :label="__('growth.incentive.totals.held')" :value="number_format($totals['held'])" icon="shield" tone="warning" />
        <x-admin.stat-tile :label="__('growth.fields.budget_used')" :value="$money($campaign->spent_minor).' / '.$money($campaign->budget_minor)" icon="banknotes" tone="info" />
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <x-admin.card :title="__('admin.common.details')">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('growth.fields.status')"><x-admin.badge :value="$campaign->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('growth.incentive.fields.quest')" :value="__('growth.incentive.quest_line', ['jobs' => $campaign->target_jobs, 'bonus' => $money($campaign->bonus_minor)])" />
                    <x-admin.detail-list.item :label="__('growth.incentive.fields.max_awards')" :value="$campaign->max_awards_per_provider" />
                    <x-admin.detail-list.item :label="__('growth.incentive.fields.min_rating')" :value="$campaign->min_rating ?? '—'" />
                    <x-admin.detail-list.item :label="__('growth.fields.min_order')"><x-admin.money :minor="$campaign->min_order_minor" :currency="$campaign->currency" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('growth.incentive.fields.max_per_customer')" :value="$campaign->max_jobs_per_customer" />
                    <x-admin.detail-list.item :label="__('growth.fields.categories')" :value="implode('، ', $scope->categories($campaign->service_category_ids)) ?: __('growth.fields.all')" />
                    <x-admin.detail-list.item :label="__('growth.fields.cities')" :value="implode('، ', $scope->cities($campaign->city_ids)) ?: __('growth.fields.all')" />
                    <x-admin.detail-list.item :label="__('growth.fields.starts_at')" :value="$when($campaign->starts_at)" />
                    <x-admin.detail-list.item :label="__('growth.fields.ends_at')" :value="$when($campaign->ends_at)" />
                    <x-admin.detail-list.item :label="__('growth.fields.status_reason')" :value="$campaign->status_reason ?? '—'" />
                </x-admin.detail-list>
            </x-admin.card>

            <x-admin.card :title="__('growth.incentive.progress_title')" :subtitle="__('growth.incentive.progress_hint')" :padding="false" class="p-4">
                <x-admin.filters-card for="{{ \App\DataTables\Admin\Growth\IncentiveProgressDataTable::TABLE_ID }}">
                    <x-admin.filters.enum name="statuses" :label="__('growth.fields.status')" :enum="\App\Enums\Growth\IncentiveProgressStatus::class" multiple />
                </x-admin.filters-card>
                <x-admin.datatable :table="$dataTable" />
            </x-admin.card>

            <x-admin.card :title="__('admin.common.activity')">
                @include('admin.partials.activity', ['logs' => $logs])
            </x-admin.card>
        </div>

        <div class="space-y-6">
            @can('adjust', $campaign)
                <x-admin.card :title="__('growth.adjust.title')" :subtitle="__('growth.adjust.hint')">
                    <x-admin.form :action="route('admin.incentive-campaigns.adjust', $campaign)" method="POST">
                        <x-admin.form.money name="budget_minor" :label="__('growth.fields.budget')" :value="$campaign->budget_minor" :currency="$campaign->currency" required />
                        <x-admin.form.textarea name="reason" :label="__('admin.common.reason')" rows="3" maxlength="1000" required />
                        <x-admin.form.actions />
                    </x-admin.form>
                </x-admin.card>
            @endcan
        </div>
    </div>
</x-admin.layouts.app>
