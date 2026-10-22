@php
    $table = \App\DataTables\Admin\Support\SupportTicketsDataTable::TABLE_ID;
    $scope = (string) request()->input('filters.scope', '');
@endphp
<x-admin.layouts.app :title="__('admin.support_tickets.title')">
    <x-admin.page-header
        :title="__('admin.support_tickets.title')"
        :subtitle="__('admin.support_tickets.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.support_tickets.title')]]"
    >
        <x-admin.button :variant="$scope === '' ? 'primary' : 'secondary'" icon="inbox" :href="route('admin.support-tickets.index')">{{ __('admin.support_tickets.quick.all') }}</x-admin.button>
        <x-admin.button :variant="$scope === 'mine' ? 'primary' : 'secondary'" icon="user" :href="route('admin.support-tickets.index', ['filters' => ['scope' => 'mine']])">{{ __('admin.support_tickets.quick.mine') }}</x-admin.button>
        <x-admin.button :variant="$scope === 'unassigned' ? 'primary' : 'secondary'" icon="users" :href="route('admin.support-tickets.index', ['filters' => ['scope' => 'unassigned']])">{{ __('admin.support_tickets.quick.unassigned') }}</x-admin.button>
    </x-admin.page-header>

    <x-admin.filters-card for="{{ $table }}">
        <x-admin.filters.text name="number" :label="__('admin.support_tickets.fields.number')" />
        <x-admin.filters.enum name="statuses" :label="__('admin.support_tickets.fields.status')" :enum="\App\Enums\Support\TicketStatus::class" multiple />
        <x-admin.filters.enum name="priorities" :label="__('admin.support_tickets.fields.priority')" :enum="\App\Enums\Support\TicketPriority::class" multiple />
        <x-admin.filters.enum name="categories" :label="__('admin.support_tickets.fields.category')" :enum="\App\Enums\Support\TicketCategory::class" multiple />
        <x-admin.filters.enum name="groups" :label="__('admin.support_tickets.fields.group')" :enum="\App\Enums\Support\TicketGroup::class" multiple />
        <x-admin.filters.remote-select name="assigned" :label="__('admin.support_tickets.fields.assignee')" lookup="admins" multiple />
        <x-admin.filters.select name="scope" :label="__('admin.support_tickets.fields.scope')" :options="['mine' => __('admin.support_tickets.quick.mine'), 'unassigned' => __('admin.support_tickets.quick.unassigned')]" />
        <x-admin.filters.boolean name="sla_breached" :label="__('admin.support_tickets.fields.sla_breached')" />
        <x-admin.filters.text name="booking" :label="__('admin.support_tickets.fields.booking')" />
        <x-admin.filters.date-range name="created" :label="__('admin.common.created_at')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
