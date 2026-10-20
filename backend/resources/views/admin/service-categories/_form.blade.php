{{-- Shared create/edit fields in tabs. $model: ?\App\Models\ServiceCategory; $familyOptions: [id => name] --}}
@php
    $defaults = app(\App\Services\Catalog\CategoryPolicyResolver::class)->defaults();
    $settingHint = static fn (string $value): string => __('admin.service_categories.hints.setting', ['value' => $value]);
    $pinValue = $model === null || $model->pin_required === null ? '' : ($model->pin_required ? '1' : '0');
    $hours = old('service_hours', $model?->service_hours ?? []);
@endphp
<x-admin.tabs :tabs="[
    'general' => __('admin.service_categories.tabs.general'),
    'booking' => __('admin.service_categories.tabs.booking'),
    'pricing' => __('admin.service_categories.tabs.pricing'),
    'dispatch' => __('admin.service_categories.tabs.dispatch'),
    'proofs' => __('admin.service_categories.tabs.proofs'),
    'payments' => __('admin.service_categories.tabs.payments'),
]">
    <x-admin.tabs.panel id="general">
        <x-admin.form.section :title="__('admin.service_categories.sections.identity')">
            <x-admin.grid>
                <x-admin.form.select name="service_family_id" :label="__('admin.service_categories.fields.family')" :options="$familyOptions" :value="$model?->service_family_id" required />
                @if ($model)
                    <x-admin.form.input name="code_display" :label="__('admin.catalog.fields.code')" :value="$model->code" :hint="__('admin.catalog.hints.code')" dir="ltr" disabled />
                @else
                    <x-admin.form.input name="code" :label="__('admin.catalog.fields.code')" :hint="__('admin.catalog.hints.code')" maxlength="40" pattern="[a-z][a-z0-9_]{1,39}" dir="ltr" required />
                @endif
                <x-admin.form.input name="icon" :label="__('admin.catalog.fields.icon')" :value="$model?->icon" :hint="__('admin.catalog.hints.icon')" maxlength="64" dir="ltr" />
                <x-admin.form.input name="map_marker" :label="__('admin.catalog.fields.map_marker')" :value="$model?->map_marker" maxlength="64" dir="ltr" />
                <x-admin.form.input name="sort_order" type="number" min="0" max="65535" :label="__('admin.catalog.fields.sort_order')" :value="$model?->sort_order ?? 0" :hint="__('admin.catalog.hints.sort_order')" />
            </x-admin.grid>
            <x-admin.form.translatable-input name="name" :label="__('admin.service_categories.fields.name')" :value="$model?->getTranslations('name') ?? []" maxlength="191" required />
            <x-admin.form.translatable-textarea name="description" :label="__('admin.service_categories.fields.description')" :value="$model?->getTranslations('description') ?? []" maxlength="1000" />
        </x-admin.form.section>
    </x-admin.tabs.panel>

    <x-admin.tabs.panel id="booking">
        <x-admin.form.section :title="__('admin.service_categories.tabs.booking')">
            <x-admin.form.select name="allowed_booking_types[]" :label="__('admin.service_categories.fields.allowed_booking_types')" :options="\App\Enums\Catalog\BookingType::options()" :value="$model?->allowed_booking_types ?? []" multiple required />
            <x-admin.grid>
                <x-admin.form.toggle name="supports_stops" :label="__('admin.service_categories.fields.supports_stops')" :checked="$model?->supports_stops ?? false" />
                <x-admin.form.input name="max_stops" type="number" min="1" max="20" :label="__('admin.service_categories.fields.max_stops')" :value="$model && $model->max_stops > 0 ? $model->max_stops : null" />
            </x-admin.grid>
        </x-admin.form.section>

        <x-admin.form.section :title="__('admin.service_categories.sections.limits')" class="mt-6">
            <x-admin.grid>
                <x-admin.form.input name="max_passengers" type="number" min="1" max="100" :label="__('admin.service_categories.fields.max_passengers')" :value="$model?->max_passengers" />
                <x-admin.form.input name="max_luggage" type="number" min="0" max="100" :label="__('admin.service_categories.fields.max_luggage')" :value="$model?->max_luggage" />
                <x-admin.form.input name="max_weight_kg" type="number" min="0" step="0.01" :label="__('admin.service_categories.fields.max_weight_kg')" :value="$model?->max_weight_kg" />
                <x-admin.form.input name="max_volume_m3" type="number" min="0" step="0.01" :label="__('admin.service_categories.fields.max_volume_m3')" :value="$model?->max_volume_m3" />
            </x-admin.grid>
        </x-admin.form.section>

        <x-admin.form.section :title="__('admin.service_categories.sections.hours')" :description="__('admin.service_categories.sections.hours_hint')" class="mt-6">
            <x-admin.form.repeater
                name="service_hours"
                :items="$hours"
                :max="28"
                :fields="[
                    ['name' => 'day', 'label' => __('admin.service_categories.fields.hours_day'), 'type' => 'select', 'options' => \App\Enums\Catalog\Weekday::options(), 'required' => true],
                    ['name' => 'from', 'label' => __('admin.service_categories.fields.hours_from'), 'type' => 'time', 'required' => true, 'attributes' => ['dir' => 'ltr']],
                    ['name' => 'to', 'label' => __('admin.service_categories.fields.hours_to'), 'type' => 'time', 'required' => true, 'attributes' => ['dir' => 'ltr']],
                ]"
            />
        </x-admin.form.section>
    </x-admin.tabs.panel>

    <x-admin.tabs.panel id="pricing">
        <x-admin.form.section :title="__('admin.service_categories.tabs.pricing')">
            <x-admin.grid>
                <x-admin.form.toggle name="negotiation_enabled" :label="__('admin.service_categories.fields.negotiation_enabled')" :hint="__('admin.service_categories.hints.negotiation')" :checked="$model?->negotiation_enabled ?? true" />
                <x-admin.form.toggle name="reference_estimate_enabled" :label="__('admin.service_categories.fields.reference_estimate_enabled')" :checked="$model?->reference_estimate_enabled ?? true" />
                <x-admin.form.money name="min_customer_fare_minor" :label="__('admin.service_categories.fields.min_customer_fare')" :value="$model?->min_customer_fare_minor" :hint="__('admin.service_categories.hints.fare_bounds')" />
                <x-admin.form.money name="max_customer_fare_minor" :label="__('admin.service_categories.fields.max_customer_fare')" :value="$model?->max_customer_fare_minor" :hint="__('admin.service_categories.hints.fare_bounds')" />
                <x-admin.form.input name="counter_offer_min_pct" type="number" min="1" max="100" :label="__('admin.service_categories.fields.counter_offer_min_pct')" :value="$model?->counter_offer_min_pct" :hint="__('admin.service_categories.hints.counter_pct')" suffix="%" />
                <x-admin.form.input name="counter_offer_max_pct" type="number" min="100" max="1000" :label="__('admin.service_categories.fields.counter_offer_max_pct')" :value="$model?->counter_offer_max_pct" :hint="__('admin.service_categories.hints.counter_pct')" suffix="%" />
            </x-admin.grid>
        </x-admin.form.section>
    </x-admin.tabs.panel>

    <x-admin.tabs.panel id="dispatch">
        <x-admin.form.section :title="__('admin.service_categories.tabs.dispatch')" :description="__('admin.service_categories.sections.fallback_hint')">
            <x-admin.grid>
                <x-admin.form.input name="offer_ttl_seconds" type="number" min="10" max="1800" :label="__('admin.service_categories.fields.offer_ttl_seconds')" :value="$model?->offer_ttl_seconds" :hint="$settingHint((string) $defaults->offerTtlSeconds)" />
                <x-admin.form.input name="search_ttl_seconds" type="number" min="30" max="3600" :label="__('admin.service_categories.fields.search_ttl_seconds')" :value="$model?->search_ttl_seconds" :hint="$settingHint((string) $defaults->searchTtlSeconds)" />
                <x-admin.form.input name="dispatch_radius_steps_km" :label="__('admin.service_categories.fields.dispatch_radius_steps_km')" :value="$model?->dispatch_radius_steps_km ? implode(', ', $model->dispatch_radius_steps_km) : null" :hint="__('admin.service_categories.hints.dispatch_radius').' '.$settingHint(implode(', ', $defaults->dispatchRadiusStepsKm))" dir="ltr" />
                <x-admin.form.input name="dispatch_batch_size" type="number" min="1" max="200" :label="__('admin.service_categories.fields.dispatch_batch_size')" :value="$model?->dispatch_batch_size" :hint="$settingHint((string) $defaults->dispatchBatchSize)" />
            </x-admin.grid>
        </x-admin.form.section>
    </x-admin.tabs.panel>

    <x-admin.tabs.panel id="proofs">
        <x-admin.form.section :title="__('admin.service_categories.tabs.proofs')">
            <x-admin.grid>
                <x-admin.form.select name="pin_required" :label="__('admin.service_categories.fields.pin_required')" :options="['1' => __('admin.service_categories.pin_options.yes'), '0' => __('admin.service_categories.pin_options.no')]" :value="$pinValue" :placeholder="__('admin.service_categories.pin_options.inherit')" :hint="__('admin.service_categories.hints.pin').' '.$settingHint($defaults->pinRequired ? __('admin.common.yes') : __('admin.common.no'))" />
                <x-admin.form.toggle name="pickup_proof_required" :label="__('admin.service_categories.fields.pickup_proof_required')" :checked="$model?->pickup_proof_required ?? false" />
                <x-admin.form.toggle name="recipient_otp_required" :label="__('admin.service_categories.fields.recipient_otp_required')" :checked="$model?->recipient_otp_required ?? false" />
            </x-admin.grid>
            <x-admin.form.select name="delivery_proof_types[]" :label="__('admin.service_categories.fields.delivery_proof_types')" :options="\App\Enums\Catalog\ProofType::options()" :value="$model?->delivery_proof_types ?? []" multiple />
        </x-admin.form.section>
    </x-admin.tabs.panel>

    <x-admin.tabs.panel id="payments">
        <x-admin.form.section :title="__('admin.service_categories.tabs.payments')">
            <x-admin.form.select name="payment_methods[]" :label="__('admin.service_categories.fields.payment_methods')" :options="\App\Enums\Payments\PaymentMethodType::options()" :value="$model?->payment_methods ?? ['cash']" multiple required />
            <x-admin.grid>
                <x-admin.form.toggle name="cash_allowed" :label="__('admin.service_categories.fields.cash_allowed')" :hint="__('admin.service_categories.hints.cash')" :checked="$model?->cash_allowed ?? true" />
                <x-admin.form.toggle name="tipping_enabled" :label="__('admin.service_categories.fields.tipping_enabled')" :checked="$model?->tipping_enabled ?? true" />
                <x-admin.form.toggle name="coupon_eligible" :label="__('admin.service_categories.fields.coupon_eligible')" :checked="$model?->coupon_eligible ?? true" />
            </x-admin.grid>
        </x-admin.form.section>
    </x-admin.tabs.panel>
</x-admin.tabs>
