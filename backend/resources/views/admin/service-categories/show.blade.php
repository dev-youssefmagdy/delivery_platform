@php
    $o = static fn (string $key): string => __('admin.service_categories.overview.'.$key);
    $f = static fn (string $key): string => __('admin.service_categories.fields.'.$key);
    $yesNo = static fn (?bool $value): string => $value === null ? '—' : ($value ? __('admin.common.yes') : __('admin.common.no'));
    $money = app(\App\Support\Money\MoneyFormatter::class);
    $currency = app(\App\Support\Money\CurrencyRegistry::class)->get((string) config('admin.default_currency'));
    $fare = static fn (?int $minor): ?string => $minor === null ? null : $money->format(\App\Support\Money\Money::of($minor, $currency));
    $labels = static fn (array $values, string $enum): string => implode(__('admin.list_separator'), array_map(static fn (string $value): string => $enum::tryFrom($value)?->label() ?? $value, $values));
@endphp
<x-admin.layouts.app :title="__('admin.service_categories.show_title')">
    <x-admin.page-header
        :title="$category->name"
        :breadcrumbs="[['label' => __('admin.service_categories.title'), 'url' => route('admin.service-categories.index')], ['label' => $category->name]]"
    >
        @can('update', $category)
            <x-admin.button variant="secondary" icon="pencil" :href="route('admin.service-categories.edit', $category)">{{ __('admin.common.edit') }}</x-admin.button>
        @endcan
        @can('publish', $category)
            <x-admin.confirm-action
                :url="route('admin.service-categories.publish', ['serviceCategory' => $category, 'from_show' => 1])"
                :title="__('admin.service_categories.confirm.publish_title')"
                :message="__('admin.service_categories.confirm.publish_message')"
                :confirm-label="__('admin.service_categories.actions.publish')"
                reason-required
                variant="success"
                icon="check-circle"
            >{{ __('admin.service_categories.actions.publish') }}</x-admin.confirm-action>
        @endcan
        @can('activate', $category)
            <x-admin.confirm-action :url="route('admin.service-categories.activate', ['serviceCategory' => $category, 'from_show' => 1])" :title="__('admin.catalog.confirm.activate_title')" :message="__('admin.service_categories.confirm.activate_message')" :confirm-label="__('admin.catalog.actions.activate')" variant="success" icon="play">{{ __('admin.catalog.actions.activate') }}</x-admin.confirm-action>
        @endcan
        @can('deactivate', $category)
            <x-admin.button variant="danger" icon="pause" data-modal-open="deactivate-category">{{ __('admin.catalog.actions.deactivate') }}</x-admin.button>
        @endcan
        @can('delete', $category)
            <x-admin.confirm-action :url="route('admin.service-categories.destroy', ['serviceCategory' => $category, 'from_show' => 1])" method="DELETE" :title="__('admin.confirm.delete_title')" :message="__('admin.confirm.delete_message')" reason-required icon="trash">{{ __('admin.common.delete') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    <div class="mb-6 space-y-3">
        @if ($category->published_version_id === null)
            <x-admin.alert type="warning">{{ __('admin.service_categories.notices.never_published') }}</x-admin.alert>
        @elseif ($hasUnpublishedChanges)
            <x-admin.alert type="info">{{ __('admin.service_categories.notices.unpublished') }}</x-admin.alert>
        @else
            <x-admin.alert type="success">{{ __('admin.service_categories.notices.up_to_date') }}</x-admin.alert>
        @endif
        @if (! $category->active && $category->published_version_id !== null)
            <x-admin.alert type="warning">{{ __('admin.service_categories.notices.inactive') }}</x-admin.alert>
        @endif
    </div>

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'overview' => ['label' => __('admin.service_categories.tabs.overview'), 'icon' => 'document'],
            'vehicles' => ['label' => __('admin.service_categories.tabs.vehicles'), 'icon' => 'cube'],
            'schema' => ['label' => __('admin.service_categories.tabs.schema'), 'icon' => 'clipboard'],
            'preview' => ['label' => __('admin.service_categories.tabs.preview'), 'icon' => 'eye'],
            'versions' => ['label' => __('admin.service_categories.tabs.versions'), 'icon' => 'archive'],
            'activity' => ['label' => __('admin.common.activity'), 'icon' => 'clock'],
        ]">
            <x-admin.tabs.panel id="overview">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.code')"><span class="ltr-only">{{ $category->code }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="$f('family')">
                        <a class="text-brand-700 hover:underline" href="{{ route('admin.service-families.show', $category->family) }}">{{ $category->family->name }}</a>
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="$f('name').' ('.\App\Enums\Common\Locale::Arabic->label().')'" :value="$category->getTranslation('name', 'ar', false)" />
                    <x-admin.detail-list.item :label="$f('name').' ('.\App\Enums\Common\Locale::English->label().')'" :value="$category->getTranslation('name', 'en', false)" />
                    <x-admin.detail-list.item :label="$f('description')" :value="$category->getTranslation('description', app()->getLocale(), true)" full />
                    <x-admin.detail-list.item :label="$f('published_version')" :value="$category->publishedVersion ? 'v'.$category->publishedVersion->version : null" />
                    <x-admin.detail-list.item :label="__('admin.catalog.fields.active')">
                        <x-admin.badge :label="$category->active ? __('admin.common.yes') : __('admin.common.no')" :color="$category->active ? 'success' : 'neutral'" />
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="$f('allowed_booking_types')" :value="$labels($category->allowed_booking_types, \App\Enums\Catalog\BookingType::class)" />
                    <x-admin.detail-list.item :label="$f('supports_stops')" :value="$category->supports_stops ? $yesNo(true).' ('.$category->max_stops.')' : $yesNo(false)" />
                    <x-admin.detail-list.item :label="$f('service_hours')">
                        @forelse ($category->service_hours ?? [] as $window)
                            <div>{{ \App\Enums\Catalog\Weekday::tryFrom((int) $window['day'])?->label() }} <span class="ltr-only">{{ $window['from'] }} - {{ $window['to'] }}</span></div>
                        @empty
                            {{ $o('always_open') }}
                        @endforelse
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="$f('negotiation_enabled')" :value="$yesNo($category->negotiation_enabled)" />
                    <x-admin.detail-list.item :label="$f('reference_estimate_enabled')" :value="$yesNo($category->reference_estimate_enabled)" />
                    <x-admin.detail-list.item :label="$f('min_customer_fare')" :value="$fare($category->min_customer_fare_minor)" />
                    <x-admin.detail-list.item :label="$f('max_customer_fare')" :value="$fare($category->max_customer_fare_minor)" />
                    <x-admin.detail-list.item :label="$f('counter_offer_min_pct')" :value="$category->counter_offer_min_pct !== null ? $category->counter_offer_min_pct.'%' : null" />
                    <x-admin.detail-list.item :label="$f('counter_offer_max_pct')" :value="$category->counter_offer_max_pct !== null ? $category->counter_offer_max_pct.'%' : null" />
                    <x-admin.detail-list.item :label="$f('max_passengers')" :value="$category->max_passengers ?? $o('unlimited')" />
                    <x-admin.detail-list.item :label="$f('max_luggage')" :value="$category->max_luggage ?? $o('unlimited')" />
                    <x-admin.detail-list.item :label="$f('max_weight_kg')" :value="$category->max_weight_kg ?? $o('unlimited')" />
                    <x-admin.detail-list.item :label="$f('max_volume_m3')" :value="$category->max_volume_m3 ?? $o('unlimited')" />
                    <x-admin.detail-list.item :label="$f('pickup_proof_required')" :value="$yesNo($category->pickup_proof_required)" />
                    <x-admin.detail-list.item :label="$f('delivery_proof_types')" :value="$labels($category->delivery_proof_types, \App\Enums\Catalog\ProofType::class)" />
                    <x-admin.detail-list.item :label="$f('recipient_otp_required')" :value="$yesNo($category->recipient_otp_required)" />
                    <x-admin.detail-list.item :label="$f('payment_methods')" :value="$labels($category->payment_methods, \App\Enums\Payments\PaymentMethodType::class)" />
                    <x-admin.detail-list.item :label="$f('cash_allowed')" :value="$yesNo($category->cash_allowed)" />
                    <x-admin.detail-list.item :label="$f('tipping_enabled')" :value="$yesNo($category->tipping_enabled)" />
                    <x-admin.detail-list.item :label="$f('coupon_eligible')" :value="$yesNo($category->coupon_eligible)" />
                </x-admin.detail-list>

                <h3 class="mt-8 mb-3 text-sm font-semibold text-slate-900">{{ $o('effective_policy') }}</h3>
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="$f('offer_ttl_seconds')" :value="$policy->offerTtlSeconds.' ('.($category->offer_ttl_seconds === null ? $o('inherited') : $o('own')).')'" />
                    <x-admin.detail-list.item :label="$f('search_ttl_seconds')" :value="$policy->searchTtlSeconds.' ('.($category->search_ttl_seconds === null ? $o('inherited') : $o('own')).')'" />
                    <x-admin.detail-list.item :label="$f('dispatch_radius_steps_km')" :value="implode(', ', $policy->dispatchRadiusStepsKm).' ('.($category->dispatch_radius_steps_km === null ? $o('inherited') : $o('own')).')'" />
                    <x-admin.detail-list.item :label="$f('dispatch_batch_size')" :value="$policy->dispatchBatchSize.' ('.($category->dispatch_batch_size === null ? $o('inherited') : $o('own')).')'" />
                    <x-admin.detail-list.item :label="$f('pin_required')" :value="$yesNo($policy->pinRequired).' ('.($category->pin_required === null ? $o('inherited') : $o('own')).')'" />
                </x-admin.detail-list>
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="vehicles">
                @can('update', $category)
                    <x-admin.form :action="route('admin.service-categories.vehicle-categories.update', $category)" method="PUT">
                        <x-admin.form.section :title="$f('vehicle_categories')" :description="__('admin.service_categories.vehicles.hint')">
                            @if ($vehicleOptions === [])
                                <x-admin.alert type="info">{{ __('admin.service_categories.vehicles.empty') }}</x-admin.alert>
                            @else
                                <x-admin.form.select name="vehicle_category_ids[]" :label="$f('vehicle_categories')" :options="$vehicleOptions" :value="$linkedVehicleIds" multiple />
                            @endif
                        </x-admin.form.section>
                        <x-admin.form.actions :submit="__('admin.service_categories.actions.save_vehicles')" />
                    </x-admin.form>
                @else
                    <ul class="list-disc ps-5 text-sm text-slate-700">
                        @foreach ($category->vehicleCategories as $vehicle)
                            <li>{{ $vehicle->name }}</li>
                        @endforeach
                    </ul>
                @endcan
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="schema">
                @include('admin.service-categories._schema-builder', ['category' => $category, 'draft' => $draft, 'builderRows' => $builderRows])
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="preview">
                @include('admin.service-categories._schema-preview', ['draft' => $draft])
            </x-admin.tabs.panel>

            <x-admin.tabs.panel id="versions" :url="route('admin.service-categories.versions', $category)" />
            <x-admin.tabs.panel id="activity" :url="route('admin.service-categories.activity', $category)" />
        </x-admin.tabs>
    </x-admin.card>

    @can('deactivate', $category)
        <x-admin.modal id="deactivate-category" :title="__('admin.service_categories.deactivate_form.title')" size="lg">
            <x-admin.form :action="route('admin.service-categories.deactivate', ['serviceCategory' => $category, 'from_show' => 1])" method="POST">
                <p class="mb-4 text-sm text-slate-600">{{ __('admin.service_categories.confirm.deactivate_message') }}</p>
                @if ($activeBookings > 0)
                    <x-admin.alert type="warning" class="mb-4">{{ __('admin.service_categories.confirm.active_bookings', ['count' => $activeBookings]) }}</x-admin.alert>
                    <x-admin.form.checkbox name="confirm_active_bookings" :label="__('admin.service_categories.confirm.confirm_active_bookings')" id="deactivate-confirm-bookings" class="mb-4" />
                @endif
                <x-admin.form.textarea name="reason" :label="__('admin.common.reason')" :hint="__('admin.service_categories.deactivate_form.reason_hint')" id="deactivate-reason" required minlength="{{ config('admin.reason.min') }}" maxlength="{{ config('admin.reason.max') }}" />
                <x-admin.form.actions :submit="__('admin.catalog.actions.deactivate')">
                    <x-admin.button variant="secondary" x-on:click="$dispatch('admin-modal:close', { id: 'deactivate-category' })">{{ __('admin.common.cancel') }}</x-admin.button>
                </x-admin.form.actions>
            </x-admin.form>
        </x-admin.modal>
    @endcan
</x-admin.layouts.app>
