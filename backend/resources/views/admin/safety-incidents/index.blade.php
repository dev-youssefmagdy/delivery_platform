@php
    $table = \App\DataTables\Admin\Safety\SafetyIncidentsDataTable::TABLE_ID;
    $scope = (string) request()->input('filters.scope', '');
@endphp
<x-admin.layouts.app :title="__('safety.admin.title')">
    <x-admin.page-header
        :title="__('safety.admin.title')"
        :subtitle="__('safety.admin.subtitle', ['urgent' => $urgentNew, 'active' => $active])"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('safety.admin.title')]]"
    >
        <x-admin.live-refresh :table="$table" :seconds="15" />
        <x-admin.button :variant="$scope === '' ? 'primary' : 'secondary'" icon="inbox" :href="route('admin.safety-incidents.index')">{{ __('safety.admin.quick.all') }}</x-admin.button>
        <x-admin.button :variant="$scope === 'urgent' ? 'primary' : 'secondary'" icon="bolt" :href="route('admin.safety-incidents.index', ['filters' => ['scope' => 'urgent']])">{{ __('safety.admin.quick.urgent') }}@if ($urgentNew > 0) ({{ $urgentNew }})@endif</x-admin.button>
        <x-admin.button :variant="$scope === 'mine' ? 'primary' : 'secondary'" icon="user" :href="route('admin.safety-incidents.index', ['filters' => ['scope' => 'mine']])">{{ __('safety.admin.quick.mine') }}</x-admin.button>
        <x-admin.button :variant="$scope === 'unassigned' ? 'primary' : 'secondary'" icon="users" :href="route('admin.safety-incidents.index', ['filters' => ['scope' => 'unassigned']])">{{ __('safety.admin.quick.unassigned') }}</x-admin.button>
        @can('exportRestricted', \App\Models\SafetyIncident::class)
            <x-admin.button variant="secondary" icon="download" :data-export-url="route('admin.safety-incidents.export')" data-export-table="{{ $table }}">{{ __('admin.common.export') }}</x-admin.button>
        @endcan
    </x-admin.page-header>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.text name="number" :label="__('safety.admin.fields.number')" />
        <x-admin.filters.enum name="severities" :label="__('safety.admin.fields.severity')" :enum="\App\Enums\Safety\IncidentSeverity::class" multiple />
        <x-admin.filters.enum name="statuses" :label="__('safety.admin.fields.status')" :enum="\App\Enums\Safety\IncidentStatus::class" multiple />
        <x-admin.filters.enum name="categories" :label="__('safety.admin.fields.category')" :enum="\App\Enums\Safety\IncidentCategory::class" multiple />
        <x-admin.filters.enum name="priorities" :label="__('safety.admin.fields.priority')" :enum="\App\Enums\Safety\IncidentPriority::class" multiple />
        <x-admin.filters.remote-select name="assigned" :label="__('safety.admin.fields.assignee')" lookup="admins" multiple />
        <x-admin.filters.select name="trigger" :label="__('safety.admin.fields.trigger')" :options="\App\Enums\Safety\IncidentTrigger::options()" />
        <x-admin.filters.boolean name="sla_breached" :label="__('safety.admin.fields.sla_breached')" />
        <x-admin.filters.boolean name="legal_hold" :label="__('safety.admin.legal_hold')" />
        <x-admin.filters.text name="booking" :label="__('safety.admin.fields.booking')" />
        <x-admin.filters.date-range name="created" :label="__('admin.common.created_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
