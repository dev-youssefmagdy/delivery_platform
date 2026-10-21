{{-- Overview tab. Expects $details, $booking, $format, $serviceName, $pickupLat, $pickupLng (see show.blade.php). Phones are masked; addresses need bookings.view_pii. --}}
<div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
    <div class="space-y-8 xl:col-span-2">
        <section>
            <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.sections.summary') }}</h3>
            <x-admin.detail-list :cols="3">
                <x-admin.detail-list.item :label="__('admin.bookings.fields.status')"><x-admin.badge :value="$booking->status" /></x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.bookings.fields.service')" :value="$serviceName" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.booking_type')" :value="$booking->booking_type->label()" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.city')" :value="$booking->city->name" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.zone')" :value="$booking->serviceZone?->name" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.created')" :value="$format($booking->created_at)" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.scheduled_at')" :value="$format($booking->scheduled_at)" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.search_expires_at')" :value="$booking->status->isSearching() ? $format($booking->search_expires_at) : null" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.request_version')" :value="$booking->request_version" />
            </x-admin.detail-list>
        </section>

        <section>
            <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.sections.parties') }}</h3>
            <x-admin.detail-list>
                <x-admin.detail-list.item :label="__('admin.bookings.fields.customer')">
                    <x-admin.user-chip :name="$booking->customer->name ?? __('admin.customers.unnamed')" :subtitle="$details->customerPhoneMasked" :href="auth('admin')->user()?->can('customers.view') ? route('admin.customers.show', $booking->customer) : null" />
                </x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.bookings.fields.provider')">
                    @if ($booking->provider)
                        <x-admin.user-chip :name="$booking->provider->user->name ?? __('admin.providers.unnamed')" :subtitle="$details->providerPhoneMasked" :href="auth('admin')->user()?->can('drivers.view') ? route('admin.providers.show', $booking->provider) : null" />
                    @endif
                </x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.bookings.fields.provider_selected_at')" :value="$format($booking->provider_selected_at)" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.heading_at')" :value="$format($booking->heading_at)" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.arrived_at')" :value="$format($booking->arrived_at)" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.started_at')" :value="$format($booking->started_at)" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.completed_at')" :value="$format($booking->completed_at)" />
            </x-admin.detail-list>
        </section>

        <section>
            <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.sections.money') }}</h3>
            <x-admin.detail-list :cols="3">
                <x-admin.detail-list.item :label="__('admin.bookings.fields.proposed_fare')"><x-admin.money :minor="$booking->proposed_fare_minor" :currency="$booking->currency" /></x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.bookings.fields.agreed_fare')">
                    @if ($booking->agreed_fare_minor !== null)<x-admin.money :minor="$booking->agreed_fare_minor" :currency="$booking->currency" />@endif
                </x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.bookings.fields.final_fare')">
                    @if ($booking->final_fare_minor !== null)<x-admin.money :minor="$booking->final_fare_minor" :currency="$booking->currency" />@endif
                </x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.bookings.fields.payment_method')" :value="$booking->payment_method->label()" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.payment_status')"><x-admin.badge :value="$booking->payment_status" /></x-admin.detail-list.item>
                <x-admin.detail-list.item :label="__('admin.bookings.fields.reference')">
                    @php($estimate = (array) ($booking->reference_estimate ?? []))
                    @if (($estimate['available'] ?? false) === true)
                        <x-admin.money :minor="(int) $estimate['low_minor']" :currency="$booking->currency" /> – <x-admin.money :minor="(int) $estimate['high_minor']" :currency="$booking->currency" />
                    @endif
                </x-admin.detail-list.item>
            </x-admin.detail-list>
        </section>

        <section>
            <h3 class="mb-3 text-sm font-semibold text-slate-800">{{ __('admin.bookings.sections.route') }}</h3>
            @if ($details->canViewPii)
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.bookings.fields.pickup')" :value="implode(' — ', array_filter([$booking->pickup_address['title'] ?? null, $booking->pickup_address['line'] ?? null])) ?: null" full />
                    <x-admin.detail-list.item :label="__('admin.bookings.fields.dropoff')" :value="implode(' — ', array_filter([$booking->dropoff_address['title'] ?? null, $booking->dropoff_address['line'] ?? null])) ?: null" full />
                    @foreach ($booking->stops as $stop)
                        <x-admin.detail-list.item :label="__('admin.bookings.fields.stop', ['n' => $stop->position])" :value="implode(' — ', array_filter([$stop->address['title'] ?? null, $stop->address['line'] ?? null])) ?: null" full />
                    @endforeach
                </x-admin.detail-list>
            @else
                <p class="text-sm text-slate-500">{{ __('admin.bookings.route_masked') }}</p>
            @endif
            @php($route = (array) ($booking->route_snapshot ?? []))
            <x-admin.detail-list :cols="3" class="mt-4">
                <x-admin.detail-list.item :label="__('admin.bookings.fields.distance')" :value="isset($route['distance_m']) ? number_format($route['distance_m'] / 1000, 1).' '.__('admin.bookings.dispatch.km') : null" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.duration')" :value="isset($route['duration_s']) ? __('admin.bookings.minutes', ['count' => (int) round($route['duration_s'] / 60)]) : null" />
                <x-admin.detail-list.item :label="__('admin.bookings.fields.stops')" :value="$booking->stops->count()" />
            </x-admin.detail-list>
        </section>
    </div>

    <aside class="space-y-3">
        <h3 class="text-sm font-semibold text-slate-800">{{ __('admin.bookings.sections.map') }}</h3>
        <x-admin.map-preview :lat="$pickupLat" :lng="$pickupLng" :label="__('admin.bookings.sections.map')" class="h-72" />
        @unless ($details->canViewPii)
            <p class="text-xs text-slate-500">{{ __('admin.bookings.map_rounded') }}</p>
        @endunless
    </aside>
</div>
