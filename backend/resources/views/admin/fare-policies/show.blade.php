@php
    $currency = app(\App\Support\Money\CurrencyRegistry::class)->get($policy->currency);
    $money = app(\App\Support\Money\MoneyFormatter::class);
    $amount = static fn (int $minor): string => $money->format(\App\Support\Money\Money::of($minor, $currency));
    $state = \App\Enums\Pricing\FarePolicyState::of($policy);
    $tz = (string) config('admin.display_timezone', 'UTC');
    $when = static fn (?\Carbon\CarbonInterface $at): ?string => $at?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
@endphp
<x-admin.layouts.app :title="__('admin.fare_policies.show_title')">
    <x-admin.page-header
        :title="$policy->serviceCategory->name"
        :subtitle="$policy->serviceZone?->name ?? __('admin.fare_policies.all_zones')"
        :breadcrumbs="[['label' => __('admin.fare_policies.title'), 'url' => route('admin.fare-policies.index')], ['label' => '#'.$policy->id]]"
    >
        @can('update', $policy)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.fare-policies.edit', $policy)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('activate', $policy)
            <x-admin.confirm-action :url="route('admin.fare-policies.activate', ['farePolicy' => $policy, 'from_show' => 1])" :title="__('admin.fare_policies.confirm.activate_title')" :message="__('admin.fare_policies.confirm.activate_message')" :confirm-label="__('admin.catalog.actions.activate')" variant="success" icon="play">{{ __('admin.catalog.actions.activate') }}</x-admin.confirm-action>
        @endcan
        @can('deactivate', $policy)
            <x-admin.confirm-action :url="route('admin.fare-policies.deactivate', ['farePolicy' => $policy, 'from_show' => 1])" :title="__('admin.fare_policies.confirm.deactivate_title')" :message="__('admin.fare_policies.confirm.deactivate_message')" :confirm-label="__('admin.catalog.actions.deactivate')" reason-required icon="pause">{{ __('admin.catalog.actions.deactivate') }}</x-admin.confirm-action>
        @endcan
        @can('delete', $policy)
            <x-admin.confirm-action :url="route('admin.fare-policies.destroy', ['farePolicy' => $policy, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <x-admin.alert type="info" class="mb-6">{{ __('admin.fare_policies.no_surge_notice') }}</x-admin.alert>

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'document'],
            'calculator' => ['label' => __('admin.fare_policies.tabs.calculator'), 'icon' => 'banknotes'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.state')"><x-admin.badge :value="$state" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.service')" :value="$policy->serviceCategory->name" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.zone')" :value="$policy->serviceZone?->name ?? __('admin.fare_policies.all_zones')" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.formula')" :value="$policy->formula_version->label()" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.effective_from')" :value="$when($policy->effective_from)" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.effective_to')" :value="$when($policy->effective_to) ?? __('admin.fare_policies.open_ended')" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.base')" :value="$amount($policy->base_minor)" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.per_km')" :value="$amount($policy->per_km_minor)" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.per_min')" :value="$amount($policy->per_min_minor)" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.stop_fee')" :value="$amount($policy->stop_fee_minor)" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.min_fare')" :value="$amount($policy->min_fare_minor)" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.rounding_step')" :value="$amount($policy->rounding_step_minor)" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.range_low')" :value="rtrim(rtrim(number_format($policy->range_low_bp / 100, 2, '.', ''), '0'), '.').'%'" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.range_high')" :value="rtrim(rtrim(number_format($policy->range_high_bp / 100, 2, '.', ''), '0'), '.').'%'" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.bookings')" :value="$bookingCount" />
                    <x-admin.detail-list.item :label="__('admin.fare_policies.fields.notes')" :value="$policy->notes" />
                </x-admin.detail-list>

                <h3 class="mb-3 mt-8 text-sm font-semibold text-slate-800">{{ __('admin.fare_policies.sections.surcharges') }}</h3>
                @if (($policy->attribute_surcharges ?? []) === [])
                    <p class="text-sm text-slate-500">{{ __('admin.fare_policies.surcharges.none') }}</p>
                @else
                    <ul class="divide-y divide-slate-100 rounded-(--radius-control) border border-slate-200 text-sm">
                        @foreach ($policy->attribute_surcharges as $row)
                            <li class="flex flex-wrap items-center justify-between gap-2 px-4 py-2.5">
                                <span><span class="ltr-only font-medium">{{ $row['field'] }}</span>
                                    @if (! empty($row['match']))<span class="text-slate-500"> = {{ $row['match'] }}</span>@endif
                                    <span class="text-slate-500"> ({{ \App\Enums\Pricing\SurchargeMode::from($row['mode'])->label() }})</span></span>
                                <span class="tabular-nums">{{ $amount((int) $row['amount_minor']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="calculator">
                <p class="mb-4 text-sm text-slate-600">{{ __('admin.fare_policies.preview.intro') }}</p>
                <x-admin.form :action="route('admin.fare-policies.preview', $policy)" method="POST">
                    <x-admin.grid>
                        <x-admin.form.input name="distance_km" type="number" step="0.1" min="0" max="5000" :label="__('admin.fare_policies.preview.distance')" :value="'5'" dir="ltr" required />
                        <x-admin.form.input name="duration_min" type="number" step="1" min="0" max="10000" :label="__('admin.fare_policies.preview.duration')" :value="'15'" dir="ltr" required />
                        <x-admin.form.input name="stops" type="number" step="1" min="0" max="10" :label="__('admin.fare_policies.preview.stops')" :value="'0'" dir="ltr" />
                    </x-admin.grid>
                    <x-admin.form.repeater
                        name="answers"
                        class="mt-4"
                        :items="[]"
                        :max="10"
                        :label="__('admin.fare_policies.preview.answers')"
                        :hint="__('admin.fare_policies.preview.answers_hint')"
                        :add-label="__('admin.fare_policies.preview.add_answer')"
                        :fields="[
                            ['name' => 'key', 'label' => __('admin.fare_policies.surcharges.field'), 'type' => 'text', 'attributes' => ['maxlength' => 40, 'dir' => 'ltr']],
                            ['name' => 'value', 'label' => __('admin.fare_policies.preview.answer_value'), 'type' => 'text', 'attributes' => ['maxlength' => 100]],
                        ]"
                    />
                    <x-admin.form.actions :submit="__('admin.fare_policies.preview.calculate')" />
                </x-admin.form>
                <div id="fare-preview-result" class="mt-6" aria-live="polite"></div>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="activity" :url="route('admin.fare-policies.activity', $policy)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>
