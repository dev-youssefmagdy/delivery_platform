@php
    $state = \App\Enums\Finance\CommissionRuleState::of($rule);
    $presenter = app(\App\Support\Finance\CommissionTermsPresenter::class);
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $version = $rule->currentVersion;
@endphp
<x-admin.layouts.app :title="__('admin.commission_rules.show_title')">
    <x-admin.page-header
        :title="$rule->name"
        :subtitle="$rule->serviceCategory?->name ?? __('admin.commission_rules.all_services')"
        :breadcrumbs="[['label' => __('admin.commission_rules.title'), 'url' => route('admin.commission-rules.index')], ['label' => '#'.$rule->id]]"
    >
        @can('update', $rule)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.commission-rules.edit', $rule)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('activate', $rule)
            <x-admin.confirm-action :url="route('admin.commission-rules.activate', ['commissionRule' => $rule, 'from_show' => 1])" :title="__('admin.commission_rules.confirm.activate_title')" :message="__('admin.commission_rules.confirm.activate_message')" :confirm-label="__('admin.catalog.actions.activate')" variant="success" icon="play">{{ __('admin.catalog.actions.activate') }}</x-admin.confirm-action>
        @endcan
        @can('deactivate', $rule)
            <x-admin.confirm-action :url="route('admin.commission-rules.deactivate', ['commissionRule' => $rule, 'from_show' => 1])" :title="__('admin.commission_rules.confirm.deactivate_title')" :message="__('admin.commission_rules.confirm.deactivate_message')" :confirm-label="__('admin.catalog.actions.deactivate')" reason-required icon="pause">{{ __('admin.catalog.actions.deactivate') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'versions' => ['label' => __('admin.commission_rules.tabs.versions'), 'icon' => 'clock'],
            'calculator' => ['label' => __('admin.commission_rules.tabs.calculator'), 'icon' => 'banknotes'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'shield'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.state')"><x-admin.badge :value="$state" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.service')" :value="$rule->serviceCategory?->name ?? __('admin.commission_rules.all_services')" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.zone')" :value="$rule->serviceZone?->name ?? __('admin.commission_rules.all_zones')" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.provider_tier')" :value="$rule->provider_tier" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.campaign')" :value="$rule->campaign_code" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.currency')" :value="$rule->currency" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.priority')" :value="$rule->priority" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.effective_from')" :value="$when($rule->effective_from)" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.effective_to')" :value="$when($rule->effective_to) ?? __('admin.commission_rules.open_ended')" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.terms')" :value="$version ? $presenter->describe($version, $rule->currency) : null" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.type')" :value="$version?->type->label()" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.on_tips')" :value="$version ? ($version->commission_on_tips ? __('admin.common.yes') : __('admin.common.no')) : null" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.bookings')" :value="$bookingCount" />
                    <x-admin.detail-list.item :label="__('admin.commission_rules.fields.notes')" :value="$rule->notes" />
                </x-admin.detail-list>
                <x-admin.alert type="info" class="mt-6">{{ __('admin.commission_rules.resolution_notice') }}</x-admin.alert>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="versions">
                <p class="mb-4 text-sm text-slate-500">{{ __('admin.commission_rules.versions_intro') }}</p>
                <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200 text-sm">
                    @foreach ($rule->versions as $row)
                        <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
                            <span class="font-medium">{{ __('admin.commission_rules.version_n', ['n' => $row->version]) }}@if ($row->id === $rule->current_version_id) <span class="ms-2 text-xs text-success-700">{{ __('admin.commission_rules.current') }}</span>@endif</span>
                            <span class="text-slate-700">{{ $presenter->describe($row, $rule->currency) }}</span>
                            <span class="text-slate-500">{{ $when($row->created_at) }}@if ($row->reason) · {{ $row->reason }}@endif</span>
                        </li>
                    @endforeach
                </ul>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="calculator">
                <p class="mb-4 text-sm text-slate-600">{{ __('admin.commission_rules.preview.intro') }}</p>
                <x-admin.form :action="route('admin.commission-rules.preview', $rule)" method="POST">
                    <x-admin.grid>
                        <x-admin.form.money name="fare_minor" :label="__('admin.commission_rules.preview.fare')" :value="10000" :currency="$rule->currency" required />
                        <x-admin.form.money name="tip_minor" :label="__('admin.commission_rules.preview.tip')" :value="0" :currency="$rule->currency" />
                    </x-admin.grid>
                    <x-admin.form.actions :submit="__('admin.commission_rules.preview.calculate')" />
                </x-admin.form>
                <div id="commission-preview-result" class="mt-6" aria-live="polite"></div>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="activity" :url="route('admin.commission-rules.activity', $rule)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>
