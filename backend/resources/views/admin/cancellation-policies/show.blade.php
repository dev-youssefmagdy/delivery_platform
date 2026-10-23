@php
    $currency = app(\App\Support\Money\CurrencyRegistry::class)->get((string) app(\App\Services\Settings\SettingsService::class)->string(\App\Enums\Settings\SettingKey::DefaultCurrency));
    $money = app(\App\Support\Money\MoneyFormatter::class);
    $amount = static fn (int $minor): string => $money->format(\App\Support\Money\Money::of($minor, $currency));
    $state = \App\Enums\Booking\CancellationPolicyState::of($policy);
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
    $reasonLabels = static fn (array $codes): string => implode(__('admin.list_separator'), array_map(static fn (string $code): string => \App\Enums\Booking\CancellationReason::tryFrom($code)?->label() ?? $code, $codes));
    $ruleValue = static function (array $rule) use ($amount): string {
        $type = \App\Enums\Booking\CancellationFeeType::tryFrom((string) $rule['fee_type']);

        return match ($type) {
            \App\Enums\Booking\CancellationFeeType::Fixed => $amount((int) $rule['value']),
            \App\Enums\Booking\CancellationFeeType::Percent => rtrim(rtrim(number_format(((int) $rule['value']) / 100, 2, '.', ''), '0'), '.').'%'.(isset($rule['cap_minor']) ? ' ('.__('admin.cancellation_policies.rules.cap').' '.$amount((int) $rule['cap_minor']).')' : ''),
            default => $type?->label() ?? '—',
        };
    };
@endphp
<x-admin.layouts.app :title="__('admin.cancellation_policies.show_title')">
    <x-admin.page-header
        :title="$policy->name"
        :subtitle="($policy->serviceCategory?->name ?? __('admin.cancellation_policies.all_services')).' · '.($policy->serviceZone?->name ?? __('admin.cancellation_policies.all_zones'))"
        :breadcrumbs="[['label' => __('admin.cancellation_policies.title'), 'url' => route('admin.cancellation-policies.index')], ['label' => '#'.$policy->id]]"
    >
        @can('update', $policy)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.cancellation-policies.edit', $policy)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('activate', $policy)
            <x-admin.confirm-action :url="route('admin.cancellation-policies.activate', ['cancellationPolicy' => $policy, 'from_show' => 1])" :title="__('admin.cancellation_policies.confirm.activate_title')" :message="__('admin.cancellation_policies.confirm.activate_message')" :confirm-label="__('admin.catalog.actions.activate')" variant="success" icon="play">{{ __('admin.catalog.actions.activate') }}</x-admin.confirm-action>
        @endcan
        @can('deactivate', $policy)
            <x-admin.confirm-action :url="route('admin.cancellation-policies.deactivate', ['cancellationPolicy' => $policy, 'from_show' => 1])" :title="__('admin.cancellation_policies.confirm.deactivate_title')" :message="__('admin.cancellation_policies.confirm.deactivate_message')" :confirm-label="__('admin.catalog.actions.deactivate')" reason-required icon="pause">{{ __('admin.catalog.actions.deactivate') }}</x-admin.confirm-action>
        @endcan
        @can('delete', $policy)
            <x-admin.confirm-action :url="route('admin.cancellation-policies.destroy', ['cancellationPolicy' => $policy, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'calculator' => ['label' => __('admin.cancellation_policies.preview.tab'), 'icon' => 'banknotes'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.state')"><x-admin.badge :value="$state" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.service')" :value="$policy->serviceCategory?->name ?? __('admin.cancellation_policies.all_services')" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.zone')" :value="$policy->serviceZone?->name ?? __('admin.cancellation_policies.all_zones')" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.effective_from')" :value="$when($policy->effective_from)" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.effective_to')" :value="$when($policy->effective_to) ?? __('admin.cancellation_policies.open_ended')" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.free_grace')" :value="__('admin.cancellation_policies.seconds', ['count' => $policy->free_grace_seconds])" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.free_wait')" :value="__('admin.cancellation_policies.seconds', ['count' => $policy->free_wait_seconds])" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.waiting_rate')" :value="$amount((int) $policy->waiting_rate_minor)" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.provider_no_show_after')" :value="__('admin.cancellation_policies.seconds', ['count' => $policy->provider_no_show_after_seconds])" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.require_evidence')" :value="$policy->require_no_show_evidence ? __('admin.common.yes') : __('admin.common.no')" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.customer_reasons')" :value="$reasonLabels($policy->customer_reasons)" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.provider_reasons')" :value="$reasonLabels($policy->provider_reasons)" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.bookings')" :value="$bookingCount" />
                    <x-admin.detail-list.item :label="__('admin.cancellation_policies.fields.notes')" :value="$policy->notes" />
                </x-admin.detail-list>

                <h3 class="mb-3 mt-8 text-sm font-semibold text-slate-800">{{ __('admin.cancellation_policies.sections.rules') }}</h3>
                @if (($policy->rules ?? []) === [])
                    <p class="text-sm text-slate-500">{{ __('admin.cancellation_policies.rules.none') }}</p>
                @else
                    <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200 text-sm">
                        @foreach ($policy->rules as $rule)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
                                <span>{{ \App\Enums\Common\ActorType::tryFrom($rule['actor'])?->label() }} · {{ \App\Enums\Booking\CancellationStage::tryFrom($rule['stage'])?->label() }}
                                    <span class="text-slate-500">({{ \App\Enums\Booking\CancellationFeeRecipient::tryFrom($rule['recipient'])?->label() }}@if (isset($rule['provider_share_bp'])) {{ $rule['provider_share_bp'] / 100 }}%@endif)</span>@if (($rule['after_seconds'] ?? 0) > 0) <span class="text-slate-500">· {{ __('admin.cancellation_policies.rules.after_seconds') }} {{ $rule['after_seconds'] }}</span>@endif</span>
                                <span class="tabular-nums">{{ $ruleValue($rule) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="calculator">
                <p class="mb-4 text-sm text-slate-600">{{ __('admin.cancellation_policies.preview.intro') }}</p>
                <x-admin.form :action="route('admin.cancellation-policies.preview', $policy)" method="POST">
                    <x-admin.grid>
                        <x-admin.form.select name="actor" :label="__('admin.cancellation_policies.preview.actor')" :options="[\App\Enums\Common\ActorType::Customer->value => \App\Enums\Common\ActorType::Customer->label(), \App\Enums\Common\ActorType::Provider->value => \App\Enums\Common\ActorType::Provider->label()]" value="customer" required />
                        <x-admin.form.select name="stage" :label="__('admin.cancellation_policies.preview.stage')" :options="collect([\App\Enums\Booking\CancellationStage::ProviderSelected, \App\Enums\Booking\CancellationStage::HeadingToPickup, \App\Enums\Booking\CancellationStage::Arrived])->mapWithKeys(fn ($stage) => [$stage->value => $stage->label()])->all()" value="arrived" required />
                        <x-admin.form.select name="reason_code" :label="__('admin.cancellation_policies.preview.reason')" :options="\App\Enums\Booking\CancellationReason::options()" :hint="__('admin.cancellation_policies.preview.reason_hint')" />
                        <x-admin.form.money name="fare_minor" :label="__('admin.cancellation_policies.preview.fare')" :value="10000" :currency="config('admin.default_currency')" required />
                        <x-admin.form.input name="minutes_since_selection" type="number" min="0" max="1440" :label="__('admin.cancellation_policies.preview.since_selection')" value="10" dir="ltr" required />
                        <x-admin.form.input name="minutes_since_arrival" type="number" min="0" max="1440" :label="__('admin.cancellation_policies.preview.since_arrival')" value="6" dir="ltr" />
                        <x-admin.form.checkbox name="arrival_verified" :label="__('admin.cancellation_policies.preview.arrival_verified')" value="1" :checked="true" />
                        <x-admin.form.checkbox name="report_verified" :label="__('admin.cancellation_policies.preview.report_verified')" value="1" :checked="true" />
                    </x-admin.grid>
                    <x-admin.form.actions :submit="__('admin.cancellation_policies.preview.calculate')" />
                </x-admin.form>
                <div id="cancellation-preview-result" class="mt-6" aria-live="polite"></div>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="activity" :url="route('admin.cancellation-policies.activity', $policy)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>
