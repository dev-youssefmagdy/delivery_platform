@php
    /** @var \App\Models\Vehicle $vehicle */
    $provider = $vehicle->providerProfile;
    $name = \App\DataTables\Admin\Provider\ProvidersDataTable::displayName($provider);
    $tz = (string) config('admin.display_timezone');
    $format = static fn (?\Carbon\CarbonInterface $date): ?string => $date?->copy()->setTimezone($tz)->translatedFormat('Y-m-d H:i');
@endphp
<x-admin.layouts.app :title="__('admin.vehicles.show_title')">
    <x-admin.page-header
        :title="$vehicle->plate_display"
        :subtitle="$vehicle->make.' '.$vehicle->model.' · '.$vehicle->year"
        :breadcrumbs="[['label' => __('admin.vehicles.title'), 'url' => route('admin.vehicles.index')], ['label' => $vehicle->plate_display]]"
    >
        @can('view', $provider)
            <x-admin.button variant="secondary" icon="user" :href="route('admin.providers.show', $provider)">{{ __('admin.vehicles.actions.open_provider') }}</x-admin.button>
        @endcan
        @can('approve', $vehicle)
            <x-admin.confirm-action :url="route('admin.vehicles.approve', ['vehicle' => $vehicle, 'from_show' => 1])" :title="__('admin.vehicles.confirm.approve_title')" :message="__('admin.vehicles.confirm.approve_message')" :confirm-label="__('admin.vehicles.actions.approve')" variant="success" icon="check-circle">{{ __('admin.vehicles.actions.approve') }}</x-admin.confirm-action>
        @endcan
        @can('reject', $vehicle)
            <x-admin.confirm-action :url="route('admin.vehicles.reject', ['vehicle' => $vehicle, 'from_show' => 1])" :title="__('admin.vehicles.confirm.reject_title')" :message="__('admin.vehicles.confirm.reject_message')" :confirm-label="__('admin.vehicles.actions.reject')" reason-required icon="x-mark">{{ __('admin.vehicles.actions.reject') }}</x-admin.confirm-action>
        @endcan
    </x-admin.page-header>

    @if ($vehicle->status === \App\Enums\Provider\VehicleStatus::Rejected && $vehicle->rejection_reason)
        <x-admin.alert type="danger" class="mb-6" :title="__('admin.vehicles.notices.rejected')">{{ __('admin.providers.notices.reason', ['reason' => $vehicle->rejection_reason]) }}</x-admin.alert>
    @endif

    <x-admin.card :padding="false" class="p-5">
        <x-admin.tabs :tabs="[
            'details' => ['label' => __('admin.common.details'), 'icon' => 'cube'],
            'photos' => ['label' => __('admin.vehicles.tabs.photos'), 'icon' => 'photo'],
            'documents' => ['label' => __('admin.vehicles.tabs.documents'), 'icon' => 'document'],
            'audit' => ['label' => __('admin.vehicles.tabs.audit'), 'icon' => 'shield'],
        ]">
            <x-admin.tabs.panel id="details">
                <x-admin.detail-list>
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.plate')"><span class="ltr-only font-mono">{{ $vehicle->plate_display }}</span></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.status')"><x-admin.badge :value="$vehicle->status" /></x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.category')" :value="$vehicle->vehicleCategory->name" />
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.provider')" :value="$name" />
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.make')" :value="$vehicle->make" />
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.model')" :value="$vehicle->model" />
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.year')" :value="$vehicle->year" />
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.color')" :value="$vehicle->color" />
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.passengers')" :value="$vehicle->passenger_capacity" />
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.payload')" :value="$vehicle->payload_kg" />
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.volume')" :value="$vehicle->volume_m3" />
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.capabilities')">
                        @foreach ($vehicle->capabilities as $capability)
                            <x-admin.badge :label="\App\Enums\Provider\VehicleCapability::tryFrom($capability)?->label() ?? $capability" color="neutral" :dot="false" />
                        @endforeach
                    </x-admin.detail-list.item>
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.submitted')" :value="$format($vehicle->submitted_at)" />
                    <x-admin.detail-list.item :label="__('admin.vehicles.fields.reviewed')" :value="$format($vehicle->reviewed_at)" />
                </x-admin.detail-list>

                <h3 class="mt-6 mb-2 text-sm font-semibold text-slate-800">{{ __('admin.vehicles.checklist') }}</h3>
                <ul class="divide-y divide-slate-100">
                    @forelse ($checklist as $item)
                        <li class="flex items-center justify-between gap-3 py-2 text-sm">
                            <span>{{ $item->type->name }} @unless ($item->mandatory)<span class="text-xs text-slate-500">({{ __('admin.document_types.optional') }})</span>@endunless</span>
                            <x-admin.badge :label="$item->state?->label() ?? __('admin.verification.missing')" :color="$item->state?->color()->value ?? 'neutral'" />
                        </li>
                    @empty
                        <li class="py-2 text-sm text-slate-500">{{ __('admin.verification.no_requirements') }}</li>
                    @endforelse
                </ul>
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="photos">
                <x-admin.document-viewer :items="$photoItems" />
            </x-admin.tabs.panel>
            <x-admin.tabs.panel id="documents" :url="route('admin.vehicles.tabs.documents', $vehicle)" />
            <x-admin.tabs.panel id="audit" :url="route('admin.vehicles.tabs.audit', $vehicle)" />
        </x-admin.tabs>
    </x-admin.card>
</x-admin.layouts.app>
