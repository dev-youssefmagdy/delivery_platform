<x-admin.layouts.app :title="__('admin.audit_logs.title')">
    <x-admin.page-header
        :title="__('admin.audit_logs.title')"
        :subtitle="__('admin.audit_logs.subtitle')"
        :breadcrumbs="[['label' => __('admin.common.home'), 'url' => route('admin.home')], ['label' => __('admin.audit_logs.title')]]"
    />

    <x-admin.filters-card for="audit-logs-table">
        <x-admin.filters.remote-select name="actor_id" :label="__('admin.audit_logs.fields.actor')" lookup="admins" />
        <x-admin.filters.enum name="actions" :label="__('admin.audit_logs.fields.action')" :enum="\App\Enums\Common\AuditAction::class" multiple />
        <x-admin.filters.select name="target_type" :label="__('admin.audit_logs.fields.target_type')" :options="$targetTypes" />
        <x-admin.filters.text name="target_id" :label="__('admin.audit_logs.fields.target_id')" />
        <x-admin.filters.date-range name="created_at" :label="__('admin.audit_logs.fields.time')" />
        <x-admin.filters.boolean name="has_reason" :label="__('admin.audit_logs.fields.has_reason')" />
    </x-admin.filters-card>

    <x-admin.card :padding="false" class="p-4">
        <x-admin.datatable :table="$dataTable" />
    </x-admin.card>
</x-admin.layouts.app>
